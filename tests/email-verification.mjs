// Run with `node tests/email-verification.mjs` after the local offline DB is started.
// Uses only Node built-ins and the project's PHP/PDO dependencies.
import {spawn, execFileSync} from 'node:child_process';
import {readFileSync} from 'node:fs';
import net from 'node:net';
import {randomBytes} from 'node:crypto';
import {once} from 'node:events';
import {setTimeout as delay} from 'node:timers/promises';

const database = 'clinic_otp_test_' + randomBytes(4).toString('hex');
const root = process.cwd();
const settings = JSON.parse(execFileSync('php', ['-r', `require getcwd().'/src/bootstrap.php'; echo json_encode(['DB_USERNAME'=>env('DB_USERNAME'),'DB_PASSWORD'=>env('DB_PASSWORD')]);`], {encoding:'utf8'}));
function sql(statement, parameters=[], target=database) {
  if (target !== 'postgres' && !/^clinic_otp_test_[a-f0-9]{8}$/.test(target)) throw new Error('Unsafe test database');
  return JSON.parse(execFileSync('php', ['-r', `$data=json_decode(stream_get_contents(STDIN),true); $password=trim(file_get_contents(getcwd().'/.local/offline-admin-password')); $db=new PDO('pgsql:host=127.0.0.1;port=54322;dbname='.$data['database'].';sslmode=disable','offline_admin',$password,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>true]); $q=$db->prepare($data['sql']);$q->execute($data['parameters']);echo json_encode($q->columnCount()?$q->fetchAll(PDO::FETCH_ASSOC):[]);`], {input:JSON.stringify({database:target,sql:statement,parameters}),encoding:'utf8'}));
}
let checks=0;
function check(value,label) {if(!value) throw new Error('FAIL: '+label);checks++;console.log('PASS: '+label);}
const messages=[];
let failDelivery=false;
const smtp=net.createServer(socket=>{
  socket.setEncoding('utf8'); socket.write('220 localhost test SMTP\r\n');
  let buffer='', data=false, message='';
  socket.on('data',chunk=>{
    buffer+=chunk;
    while(buffer.includes('\r\n')) {
      const end=buffer.indexOf('\r\n'),line=buffer.slice(0,end);buffer=buffer.slice(end+2);
      if(data) {
        if(line==='.') {data=false;messages.push(message);message='';socket.write('250 Queued\r\n');}
        else message+=line+'\r\n';
      } else if(/^EHLO|^HELO/.test(line)) socket.write('250-localhost\r\n250 8BITMIME\r\n');
      else if(/^DATA/.test(line)) {if(failDelivery)socket.write('451 Test delivery failure\r\n');else {data=true;socket.write('354 End with dot\r\n');}}
      else if(/^QUIT/.test(line)) socket.end('221 Bye\r\n');
      else socket.write('250 OK\r\n');
    }
  });
  socket.on('error',()=>{});
});
await new Promise(resolve=>smtp.listen(0,'127.0.0.1',resolve));
let app;
let origin;
class Session {
  cookies=new Map();
  async request(path,data) {
    const response=await fetch(origin+path,{method:data?'POST':'GET',redirect:'manual',signal:AbortSignal.timeout(15000),headers:{Cookie:[...this.cookies].map(([k,v])=>`${k}=${v}`).join('; '),...(data?{Origin:origin}:{})},body:data?new URLSearchParams({csrf:this.cookies.get('rac_csrf')||'',...data}):undefined});
    for(const cookie of response.headers.getSetCookie()){const pair=cookie.split(';')[0],i=pair.indexOf('=');this.cookies.set(pair.slice(0,i),pair.slice(i+1));}
    return {status:response.status,location:response.headers.get('location'),html:await response.text()};
  }
}
async function registration(label) {
  const session=new Session();await session.request('/register.php');
  const email=`${label}-${randomBytes(3).toString('hex')}@example.test`;
  const response=await session.request('/register.php',{full_name:'OTP Test Owner',email,phone:'09000000000',password:'Verification-test-123',confirm_password:'Verification-test-123'});
  return {session,email,response};
}
function lastCode(){const code=messages.at(-1)?.match(/Your verification code: (\d{6})/)?.[1];check(Boolean(code),'PHPMailer message contains a six-digit code');return code;}
try {
  sql('CREATE DATABASE '+database,[],'postgres');
  sql(readFileSync('database/schema.sql','utf8'));
  const portServer=net.createServer();await new Promise(resolve=>portServer.listen(0,'127.0.0.1',resolve));
  const port=portServer.address().port;await new Promise(resolve=>portServer.close(resolve));origin='http://127.0.0.1:'+port;
  app=spawn('php',['-d','variables_order=EGPCS','-S','127.0.0.1:'+port,'router.php'],{cwd:root,windowsHide:true,stdio:['ignore','ignore','pipe'],env:{...process.env,...settings,DB_HOST:'127.0.0.1',DB_PORT:'54322',DB_DATABASE:database,DB_SSLMODE:'disable',APP_ENV:'local',APP_URL:origin,APP_KEY:randomBytes(32).toString('hex'),MAIL_ENABLED:'true',SMTP_HOST:'127.0.0.1',SMTP_PORT:String(smtp.address().port),SMTP_AUTH:'false',SMTP_ENCRYPTION:'none',MAIL_FROM_ADDRESS:'clinic@example.test',MAIL_FROM_NAME:'Rebadulla Animal Care'}});
  let startup='';app.stderr.on('data',bytes=>{startup+=bytes.toString();});
  for(let i=0;i<40;i++){if(startup.includes('Development Server'))break;if(app.exitCode!==null)throw new Error('Test server failed');await delay(100);}
  const first=await registration('success');
  check(first.response.location==='/verify-email.php','registration requires email verification before login');
  check(sql('SELECT id FROM clinic.users WHERE email=?',[first.email]).length===0,'unverified account is not created');
  const code=lastCode();
  check(messages.at(-1).includes('Content-ID: <rebadulla-logo>')&&messages.at(-1).includes('image/png'),'email embeds the app logo');
  check(messages.at(-1).includes('text/html')&&messages.at(-1).includes('text/plain'),'HTML and plain-text email alternatives');
  const challenge=sql('SELECT * FROM clinic.registration_challenges WHERE email=?',[first.email])[0];
  check(challenge.code_hash!==code&&challenge.password_hash!=='Verification-test-123','codes and passwords are hashed');
  const page=await first.session.request('/verify-email.php');
  check(page.status===200&&page.html.includes('autocomplete="one-time-code"'),'accessible OTP screen');
  check(!page.html.includes(code),'OTP is never exposed in the web page');
  if(process.env.RAC_OTP_VISUAL==='1') {
    const {chromium}=await import('../.local/otp-qa/node_modules/playwright/index.mjs');
    const browser=await chromium.launch({headless:true});
    try {
      const context=await browser.newContext({viewport:{width:1440,height:1000}});
      await context.addCookies([...first.session.cookies].map(([name,value])=>({name,value,url:origin,httpOnly:true,sameSite:'Lax'})));
      await context.addInitScript(()=>{const originalNow=Date.now;Date.now=()=>originalNow()+30*60*1000;});
      const ui=await context.newPage();await ui.goto(origin+'/verify-email.php');
      check(await ui.locator('[data-otp-submit]').isEnabled(),'client clock skew does not disable a valid code');
      await ui.screenshot({path:'.local/otp-desktop.png',fullPage:true});
      await ui.locator('#verification-code').fill('123456');
      check((await ui.locator('[data-otp-digit]').allTextContents()).join('')==='123456','six visual digits follow the accessible input');
      await ui.setViewportSize({width:390,height:844});
      await ui.screenshot({path:'.local/otp-mobile.png',fullPage:true});
      check(await ui.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'mobile verification has no horizontal overflow');
      const plain=await browser.newContext({javaScriptEnabled:false});
      await plain.addCookies([...first.session.cookies].map(([name,value])=>({name,value,url:origin,httpOnly:true,sameSite:'Lax'})));
      const nojs=await plain.newPage();await nojs.goto(origin+'/verify-email.php');
      check(await nojs.locator('#verification-code').isVisible(),'OTP input works without JavaScript');
      const emailHtml=execFileSync('php',['-r',"require getcwd().'/src/bootstrap.php';$name='Jamie';$code='123456';require getcwd().'/views/emails/verification.php';"],{encoding:'utf8'}).replace('cid:rebadulla-logo','data:image/png;base64,'+readFileSync('assets/email-logo.png').toString('base64'));
      await ui.setViewportSize({width:650,height:850});await ui.setContent(emailHtml);await ui.screenshot({path:'.local/otp-email.png',fullPage:true});
    } finally {await browser.close();}
  }
  const outsider=new Session();await outsider.request('/register.php');
  check((await outsider.request('/verify-email.php',{action:'verify',code})).location==='/register.php','verification is bound to the registering browser');
  check((await first.session.request('/verify-email.php',{action:'verify',code:'bad'})).html.includes('six-digit'),'malformed code rejected');
  check((await first.session.request('/verify-email.php',{action:'verify',code:'000000'===code?'111111':'000000'})).html.includes('does not match'),'wrong code rejected');
  check((await first.session.request('/verify-email.php',{action:'verify',code})).location==='/owner/dashboard.php','correct code creates the account and signs in');
  check(sql('SELECT id FROM clinic.users WHERE email=?',[first.email]).length===1,'exactly one verified account created');
  check(sql('SELECT token_hash FROM clinic.registration_challenges WHERE email=?',[first.email]).length===0,'challenge consumed after success');
  check((await first.session.request('/verify-email.php',{action:'verify',code})).location==='/owner/dashboard.php','replay cannot create another account');

  const resend=await registration('resend');const oldCode=lastCode();const before=messages.length;
  check((await resend.session.request('/verify-email.php',{action:'resend'})).html.includes('wait'),'resend cooldown enforced on the server');
  check(messages.length===before,'cooldown does not send another email');
  sql("UPDATE clinic.registration_challenges SET sent_at=now()-interval '61 seconds' WHERE email=?",[resend.email]);
  sql("UPDATE clinic.registration_challenges SET created_at=now()-interval '61 minutes' WHERE email=?",[resend.email]);
  await registration('cleanup');
  check(sql('SELECT token_hash FROM clinic.registration_challenges WHERE email=?',[resend.email]).length===1,'cleanup preserves a still-valid older challenge');
  check((await resend.session.request('/verify-email.php',{action:'resend'})).location==='/verify-email.php?resent=1','resend succeeds after cooldown');
  const newCode=lastCode();check(newCode!==oldCode,'resend replaces the old code');
  check((await resend.session.request('/verify-email.php',{action:'verify',code:oldCode})).html.includes('does not match'),'old code invalid after resend');
  sql("UPDATE clinic.registration_challenges SET expires_at=now()-interval '1 second' WHERE email=?",[resend.email]);
  check((await resend.session.request('/verify-email.php',{action:'verify',code:newCode})).html.includes('expired'),'expired code rejected');

  const locked=await registration('locked');const lockCode=lastCode();
  for(let i=0;i<5;i++)await locked.session.request('/verify-email.php',{action:'verify',code:lockCode==='000000'?'111111':'000000'});
  check((await locked.session.request('/verify-email.php',{action:'verify',code:lockCode})).html.includes('Too many'),'five failed attempts lock the code');
  check(sql('SELECT id FROM clinic.users WHERE email=?',[locked.email]).length===0,'locked challenge cannot create account');

  failDelivery=true;const failed=await registration('smtp-failure');failDelivery=false;
  check(failed.response.status===200&&failed.response.html.includes('could not send'),'SMTP failure produces a useful registration error');
  check(sql('SELECT id FROM clinic.users WHERE email=?',[failed.email]).length===0,'SMTP failure never creates an account');
  check(sql('SELECT token_hash FROM clinic.registration_challenges WHERE email=?',[failed.email]).length===0,'SMTP failure leaves no usable challenge');

  // Every appointment change emails the owner. Failed emails stay queued and can be retried.
  const adminEmail='clinic-admin@example.test',adminPassword='Admin-test-password-1';
  const adminHash=execFileSync('php',['-r','echo password_hash($argv[1],PASSWORD_DEFAULT);',adminPassword],{encoding:'utf8'});
  sql("INSERT INTO clinic.users (role,full_name,email,password_hash) VALUES ('admin','Test Admin',?,?)",[adminEmail,adminHash]);
  const admin=new Session();await admin.request('/admin-login.php');
  check((await admin.request('/admin-login.php',{email:adminEmail,password:adminPassword})).location==='/admin/dashboard.php','admin signs in for appointment email checks');
  const owner=first.session,ownerId=sql('SELECT id FROM clinic.users WHERE email=?',[first.email])[0].id;
  const petId=sql("INSERT INTO clinic.pets (owner_id,name,species) VALUES (?,'Mail Pet','Dog') RETURNING id",[ownerId])[0].id;
  const serviceId=sql('SELECT id FROM clinic.services ORDER BY id LIMIT 1')[0].id;
  const day=sql("SELECT d::date::text AS d FROM generate_series(current_date+2,current_date+8,interval '1 day') d WHERE extract(dow FROM d)<>0 LIMIT 1")[0].d;
  const newKey=()=>randomBytes(16).toString('hex');
  const book=async time=>{const before=messages.length;const response=await owner.request('/owner/book.php',{pet_id:petId,service_id:serviceId,appt_date:day,appt_time:time,request_key:newKey()});return {id:response.location?.match(/id=(\d+)/)?.[1],sent:messages.length-before};};
  const act=async(session,role,id,action,extra={})=>{const path=`/${role}/appointment.php?id=${id}`,version=(await session.request(path)).html.match(/name="version" value="([^"]+)"/)[1],before=messages.length;const response=await session.request(path,{action,version,request_key:newKey(),...extra});return {response,sent:messages.length-before};};
  const booked=await book('09:00');check(booked.id&&booked.sent===1,'booking emails the owner');
  const ref=sql('SELECT booking_ref FROM clinic.appointments WHERE id=?',[booked.id])[0].booking_ref;
  check(messages.at(-1).includes(ref)&&messages.at(-1).includes(first.email),'appointment email names the booking and goes to the owner');
  for(const [session,role,action,extra] of [[admin,'admin','propose',{appt_date:day,appt_time:'10:00'}],[owner,'owner','accept',{}],[admin,'admin','reschedule',{appt_date:day,appt_time:'11:00'}],[owner,'owner','change',{appt_date:day,appt_time:'12:00'}],[admin,'admin','confirm',{}],[owner,'owner','cancel',{}]]){
    const result=await act(session,role,booked.id,action,extra);check(result.sent===1&&messages.at(-1).includes(ref),`${role} ${action} emails the owner`);
  }
  const adminCancel=await book('14:00');check((await act(admin,'admin',adminCancel.id,'cancel')).sent===1,'admin cancel emails the owner');
  for(const action of ['complete','miss']){
    const visit=await book('13:00');await act(admin,'admin',visit.id,'confirm');sql('UPDATE clinic.appointments SET appt_date=current_date-1 WHERE id=?',[visit.id]);
    check((await act(admin,'admin',visit.id,action)).sent===1,`admin ${action} emails the owner`);
  }
  const delayed=await book('15:00');failDelivery=true;const failure=await act(admin,'admin',delayed.id,'confirm');failDelivery=false;
  check(failure.sent===0&&failure.response.location.includes('failed=1'),'failed appointment email is reported to staff');
  check((await admin.request(failure.response.location)).html.includes('email was not sent'),'staff see the failed email warning');
  check(sql("SELECT count(*)::int AS n FROM clinic.mail_outbox WHERE status='pending'")[0].n===1,'failed email stays queued');
  check((await admin.request('/admin/mail.php')).html.includes('Send waiting emails'),'outbox offers a retry button');
  check((await admin.request('/admin/mail.php',{action:'reset'})).location==='/admin/mail.php?sent=1&failed=0','retry button sends the waiting email');
  const backlog=await book('15:30');failDelivery=true;await act(admin,'admin',backlog.id,'confirm');failDelivery=false;
  check((await act(admin,'admin',backlog.id,'cancel')).sent===2,'next appointment change also sends emails left waiting');
  check(!(await admin.request('/admin/mail.php')).html.includes('Send waiting emails'),'retry button hides when nothing is waiting');
  console.log(`EMAIL_VERIFICATION_PASSED=${checks}`);
} finally {
  if(app&&app.exitCode===null){app.kill();await once(app,'exit');}
  smtp.close();
  sql('DROP DATABASE IF EXISTS '+database+' WITH (FORCE)',[],'postgres');
}
