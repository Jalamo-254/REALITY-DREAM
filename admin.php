<?php
session_start();
require_once 'db_config.php';
if (empty($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }
function vdate($d){ if($d==='')return false; $x=DateTime::createFromFormat('Y-m-d',$d); return $x && $x->format('Y-m-d')===$d; }
function bindp($stmt,$types,&$params){ if($types==='') return true; $a=[$types]; foreach($params as $k=>&$v){$a[]=&$v;} return call_user_func_array([$stmt,'bind_param'],$a); }
function pp($v){ $a=[10,25,50,100]; $v=(int)$v; return in_array($v,$a,true)?$v:25; }
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['clear_action'])){
  $a=$_POST['clear_action']??''; $c=trim($_POST['confirm_clear']??''); $rq=ltrim((string)($_POST['return_query']??''),'?'); $r='admin.php'.($rq!==''?'?'.$rq:'');
  if($c!=='CLEAR'){ $_SESSION['admin_flash']=['type'=>'error','text'=>'Type CLEAR to confirm deletion.']; header('Location: '.$r); exit; }
  $ok=true; $n=0; $conn->begin_transaction();
  if($a==='clear_reviewed'){ $ok=$ok&&$conn->query("DELETE FROM contacts WHERE status IN ('Reviewed','Contacted')"); if($ok)$n+=$conn->affected_rows; $ok=$ok&&$conn->query("DELETE FROM enrollments WHERE status IN ('Reviewed','Contacted')"); if($ok)$n+=$conn->affected_rows; }
  elseif($a==='clear_contacts_all'){ $ok=$ok&&$conn->query("DELETE FROM contacts"); if($ok)$n+=$conn->affected_rows; }
  elseif($a==='clear_enrollments_all'){ $ok=$ok&&$conn->query("DELETE FROM enrollments"); if($ok)$n+=$conn->affected_rows; }
  elseif($a==='clear_all'){ $ok=$ok&&$conn->query("DELETE FROM contacts"); if($ok)$n+=$conn->affected_rows; $ok=$ok&&$conn->query("DELETE FROM enrollments"); if($ok)$n+=$conn->affected_rows; }
  else $ok=false;
  if($ok){ $conn->commit(); $_SESSION['admin_flash']=['type'=>'success','text'=>"Deleted {$n} submission(s)."] ; } else { $conn->rollback(); $_SESSION['admin_flash']=['type'=>'error','text'=>'Delete failed: '.$conn->error]; }
  header('Location: '.$r); exit;
}
$st=['all','New','Reviewed','Contacted'];
$c_status=$_GET['c_status']??'all'; if(!in_array($c_status,$st,true))$c_status='all';
$c_q=trim($_GET['c_q']??''); $c_from=trim($_GET['c_date_from']??''); $c_to=trim($_GET['c_date_to']??''); if(!vdate($c_from))$c_from=''; if(!vdate($c_to))$c_to='';
$c_pp=pp($_GET['c_per_page']??25); $c_page=max(1,(int)($_GET['c_page']??1));
$w=[]; $t=''; $p=[];
if($c_status!=='all'){ $w[]='status=?'; $t.='s'; $p[]=$c_status; }
if($c_from!==''){ $w[]='submitted_date>=?'; $t.='s'; $p[]=$c_from.' 00:00:00'; }
if($c_to!==''){ $w[]='submitted_date<=?'; $t.='s'; $p[]=$c_to.' 23:59:59'; }
if($c_q!==''){ $w[]='(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ? OR course LIKE ? OR message LIKE ?)'; $t.='ssssss'; $lk='%'.$c_q.'%'; for($i=0;$i<6;$i++)$p[]=$lk; }
$c_where=$w?('WHERE '.implode(' AND ',$w)):'';
$s=$conn->prepare("SELECT COUNT(*) total FROM contacts $c_where"); if($t!=='') bindp($s,$t,$p); $s->execute(); $c_total=(int)$s->get_result()->fetch_assoc()['total']; $s->close();
$c_pages=max(1,(int)ceil($c_total/$c_pp)); if($c_page>$c_pages)$c_page=$c_pages; $c_off=($c_page-1)*$c_pp;
$s=$conn->prepare("SELECT * FROM contacts $c_where ORDER BY submitted_date DESC LIMIT ? OFFSET ?"); $tt=$t.'ii'; $pp=$p; $pp[]=$c_pp; $pp[]=$c_off; bindp($s,$tt,$pp); $s->execute(); $contacts=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();
$e_status=$_GET['e_status']??'all'; if(!in_array($e_status,$st,true))$e_status='all';
$e_q=trim($_GET['e_q']??''); $e_from=trim($_GET['e_date_from']??''); $e_to=trim($_GET['e_date_to']??''); if(!vdate($e_from))$e_from=''; if(!vdate($e_to))$e_to='';
$e_pp=pp($_GET['e_per_page']??25); $e_page=max(1,(int)($_GET['e_page']??1));
$w=[]; $t=''; $p=[];
if($e_status!=='all'){ $w[]='status=?'; $t.='s'; $p[]=$e_status; }
if($e_from!==''){ $w[]='submitted_at>=?'; $t.='s'; $p[]=$e_from.' 00:00:00'; }
if($e_to!==''){ $w[]='submitted_at<=?'; $t.='s'; $p[]=$e_to.' 23:59:59'; }
if($e_q!==''){ $w[]='(name LIKE ? OR email LIKE ? OR phone LIKE ? OR course LIKE ? OR notes LIKE ? OR study_mode LIKE ? OR intake_month LIKE ?)'; $t.='sssssss'; $lk='%'.$e_q.'%'; for($i=0;$i<7;$i++)$p[]=$lk; }
$e_where=$w?('WHERE '.implode(' AND ',$w)):'';
$s=$conn->prepare("SELECT COUNT(*) total FROM enrollments $e_where"); if($t!=='') bindp($s,$t,$p); $s->execute(); $e_total=(int)$s->get_result()->fetch_assoc()['total']; $s->close();
$e_pages=max(1,(int)ceil($e_total/$e_pp)); if($e_page>$e_pages)$e_page=$e_pages; $e_off=($e_page-1)*$e_pp;
$s=$conn->prepare("SELECT * FROM enrollments $e_where ORDER BY submitted_at DESC LIMIT ? OFFSET ?"); $tt=$t.'ii'; $pp=$p; $pp[]=$e_pp; $pp[]=$e_off; bindp($s,$tt,$pp); $s->execute(); $enrolls=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();
$ct=(int)$conn->query("SELECT COUNT(*) total FROM contacts")->fetch_assoc()['total'];
$et=(int)$conn->query("SELECT COUNT(*) total FROM enrollments")->fetch_assoc()['total'];
$sum=['New'=>0,'Reviewed'=>0]; $rs=$conn->query("SELECT status,COUNT(*) total FROM (SELECT status FROM contacts UNION ALL SELECT status FROM enrollments) x GROUP BY status"); if($rs){while($r=$rs->fetch_assoc()){ if(isset($sum[$r['status']]))$sum[$r['status']]=(int)$r['total']; }}
$flash=$_SESSION['admin_flash']??null; unset($_SESSION['admin_flash']); $base=$_GET;
?>
<!doctype html><html lang='en'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'><title>Admin Dashboard</title>
<script src='https://cdn.tailwindcss.com'></script><link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
<link href='https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap' rel='stylesheet'>
<style>body{font-family:Manrope,sans-serif;background:linear-gradient(180deg,#f8fafc,#eef2ff)}.panel{background:#fff;border:1px solid #e2e8f0;border-radius:16px}.pill{display:inline-flex;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700}.new{background:#dcfce7;color:#166534}.rev{background:#dbeafe;color:#1e40af}.con{background:#fef3c7;color:#92400e}.def{background:#f1f5f9;color:#334155}.dark-mode{background:linear-gradient(180deg,#020617,#0f172a);color:#e2e8f0}.dark-mode .panel{background:#0f172a;border-color:#334155}.dark-mode table *{color:#e2e8f0!important}</style></head><body>
<div class='max-w-7xl mx-auto p-4 md:p-8'>
<div class='rounded-2xl bg-slate-900 text-white p-6 mb-6 flex justify-between items-center'><div><h1 class='text-3xl font-extrabold'>Admin Dashboard</h1><p class='text-slate-300'>Contact and Enrollment are separated below.</p></div><button id='darkModeToggle' class='px-4 py-2 rounded-full bg-white/15 border border-white/20'><i class='fas fa-moon'></i> <span id='darkModeLabel'>Dark</span></button></div>
<?php if($flash): ?><div class='mb-4 p-3 rounded-xl border <?php echo $flash['type']==='success'?'bg-emerald-50 border-emerald-200 text-emerald-800':'bg-rose-50 border-rose-200 text-rose-800'; ?>'><?php echo htmlspecialchars($flash['text']); ?></div><?php endif; ?>
<div class='grid grid-cols-2 md:grid-cols-4 gap-4 mb-6'>
<div class='panel p-4'><p class='text-sm'>Total</p><p class='text-2xl font-extrabold'><?php echo $ct+$et; ?></p></div>
<div class='panel p-4'><p class='text-sm'>Contacts</p><p class='text-2xl font-extrabold'><?php echo $ct; ?></p></div>
<div class='panel p-4'><p class='text-sm'>Enrollments</p><p class='text-2xl font-extrabold'><?php echo $et; ?></p></div>
<div class='panel p-4'><p class='text-sm'>New / Reviewed</p><p class='text-2xl font-extrabold'><?php echo $sum['New']; ?> / <?php echo $sum['Reviewed']; ?></p></div>
</div>

<div class='panel p-4 mb-6'>
<h2 class='text-xl font-bold mb-3'>Contact Submissions</h2>
<form method='get' class='grid grid-cols-1 md:grid-cols-6 gap-3 mb-4'>
<input name='c_q' value='<?php echo htmlspecialchars($c_q); ?>' class='md:col-span-2 border rounded-lg px-3 py-2' placeholder='Search contacts'>
<select name='c_status' class='border rounded-lg px-3 py-2'><option value='all' <?php echo $c_status==='all'?'selected':''; ?>>All</option><option value='New' <?php echo $c_status==='New'?'selected':''; ?>>New</option><option value='Reviewed' <?php echo $c_status==='Reviewed'?'selected':''; ?>>Reviewed</option><option value='Contacted' <?php echo $c_status==='Contacted'?'selected':''; ?>>Contacted</option></select>
<input type='date' name='c_date_from' value='<?php echo htmlspecialchars($c_from); ?>' class='border rounded-lg px-3 py-2'>
<input type='date' name='c_date_to' value='<?php echo htmlspecialchars($c_to); ?>' class='border rounded-lg px-3 py-2'>
<select name='c_per_page' class='border rounded-lg px-3 py-2'><option <?php echo $c_pp===10?'selected':''; ?>>10</option><option <?php echo $c_pp===25?'selected':''; ?>>25</option><option <?php echo $c_pp===50?'selected':''; ?>>50</option><option <?php echo $c_pp===100?'selected':''; ?>>100</option></select>
<input type='hidden' name='e_q' value='<?php echo htmlspecialchars($e_q); ?>'><input type='hidden' name='e_status' value='<?php echo htmlspecialchars($e_status); ?>'><input type='hidden' name='e_date_from' value='<?php echo htmlspecialchars($e_from); ?>'><input type='hidden' name='e_date_to' value='<?php echo htmlspecialchars($e_to); ?>'><input type='hidden' name='e_per_page' value='<?php echo $e_pp; ?>'><input type='hidden' name='e_page' value='<?php echo $e_page; ?>'>
<button class='bg-blue-600 text-white rounded-lg px-4 py-2'>Filter Contacts</button>
</form>
<div class='overflow-x-auto'><table class='w-full text-sm'><thead><tr class='border-b'><th class='text-left p-2'>Name</th><th class='text-left p-2'>Email</th><th class='text-left p-2'>Phone</th><th class='text-left p-2'>Course</th><th class='text-left p-2'>Date</th><th class='text-left p-2'>Status</th><th class='text-center p-2'>Action</th></tr></thead><tbody>
<?php if($contacts): foreach($contacts as $r): ?>
<tr class='border-b'><td class='p-2 font-semibold'><?php echo htmlspecialchars($r['first_name'].' '.$r['last_name']); ?></td><td class='p-2'><a class='text-blue-600' href='mailto:<?php echo htmlspecialchars($r['email']); ?>'><?php echo htmlspecialchars($r['email']); ?></a></td><td class='p-2'><?php echo htmlspecialchars($r['phone']); ?></td><td class='p-2'><?php echo htmlspecialchars($r['course']); ?></td><td class='p-2'><?php echo date('M d, Y H:i',strtotime($r['submitted_date'])); ?></td><td class='p-2'><?php $sc=$r['status']==='New'?'new':($r['status']==='Reviewed'?'rev':($r['status']==='Contacted'?'con':'def')); ?><span class='pill <?php echo $sc; ?>'><?php echo htmlspecialchars($r['status']); ?></span></td><td class='p-2 text-center'><button class='text-blue-600' onclick="viewDetails('Contact Message',`<?php echo addslashes(htmlspecialchars($r['message'])); ?>`)">View</button><?php if(!empty($r['attachment'])): ?><div class='mt-1'><a href='<?php echo htmlspecialchars($r['attachment']); ?>' target='_blank' class='text-emerald-600 text-xs font-semibold hover:underline'>View Attachment</a></div><?php endif; ?><form method='post' action='update_status.php' class='mt-1'><input type='hidden' name='id' value='<?php echo (int)$r['id']; ?>'><input type='hidden' name='record_type' value='contact'><input type='hidden' name='return_query' value='<?php echo htmlspecialchars($_SERVER['QUERY_STRING']??''); ?>'><select name='status' onchange='this.form.submit()' class='border rounded px-2 py-1'><option value='New' <?php echo $r['status']==='New'?'selected':''; ?>>New</option><option value='Reviewed' <?php echo $r['status']==='Reviewed'?'selected':''; ?>>Reviewed</option><option value='Contacted' <?php echo $r['status']==='Contacted'?'selected':''; ?>>Contacted</option></select></form></td></tr>
<?php endforeach; else: ?><tr><td colspan='7' class='p-4 text-center text-slate-500'>No contacts found.</td></tr><?php endif; ?>
</tbody></table></div>
<?php $cp='?'.http_build_query(array_merge($base,['c_page'=>max(1,$c_page-1)])); $cn='?'.http_build_query(array_merge($base,['c_page'=>min($c_pages,$c_page+1)])); ?>
<div class='mt-3 flex justify-between items-center'><small>Showing <?php echo $c_total?($c_off+1):0; ?> - <?php echo min($c_off+$c_pp,$c_total); ?> of <?php echo $c_total; ?></small><div class='flex gap-2'><a class='px-3 py-1 border rounded <?php echo $c_page<=1?'opacity-40 pointer-events-none':''; ?>' href='<?php echo htmlspecialchars($cp); ?>'>Prev</a><a class='px-3 py-1 border rounded <?php echo $c_page>=$c_pages?'opacity-40 pointer-events-none':''; ?>' href='<?php echo htmlspecialchars($cn); ?>'>Next</a></div></div>
</div>

<div class='panel p-4 mb-6'>
<h2 class='text-xl font-bold mb-3'>Enrollment Submissions</h2>
<form method='get' class='grid grid-cols-1 md:grid-cols-6 gap-3 mb-4'>
<input name='e_q' value='<?php echo htmlspecialchars($e_q); ?>' class='md:col-span-2 border rounded-lg px-3 py-2' placeholder='Search enrollments'>
<select name='e_status' class='border rounded-lg px-3 py-2'><option value='all' <?php echo $e_status==='all'?'selected':''; ?>>All</option><option value='New' <?php echo $e_status==='New'?'selected':''; ?>>New</option><option value='Reviewed' <?php echo $e_status==='Reviewed'?'selected':''; ?>>Reviewed</option><option value='Contacted' <?php echo $e_status==='Contacted'?'selected':''; ?>>Contacted</option></select>
<input type='date' name='e_date_from' value='<?php echo htmlspecialchars($e_from); ?>' class='border rounded-lg px-3 py-2'>
<input type='date' name='e_date_to' value='<?php echo htmlspecialchars($e_to); ?>' class='border rounded-lg px-3 py-2'>
<select name='e_per_page' class='border rounded-lg px-3 py-2'><option <?php echo $e_pp===10?'selected':''; ?>>10</option><option <?php echo $e_pp===25?'selected':''; ?>>25</option><option <?php echo $e_pp===50?'selected':''; ?>>50</option><option <?php echo $e_pp===100?'selected':''; ?>>100</option></select>
<input type='hidden' name='c_q' value='<?php echo htmlspecialchars($c_q); ?>'><input type='hidden' name='c_status' value='<?php echo htmlspecialchars($c_status); ?>'><input type='hidden' name='c_date_from' value='<?php echo htmlspecialchars($c_from); ?>'><input type='hidden' name='c_date_to' value='<?php echo htmlspecialchars($c_to); ?>'><input type='hidden' name='c_per_page' value='<?php echo $c_pp; ?>'><input type='hidden' name='c_page' value='<?php echo $c_page; ?>'>
<button class='bg-blue-600 text-white rounded-lg px-4 py-2'>Filter Enrollments</button>
</form>
<div class='overflow-x-auto'><table class='w-full text-sm'><thead><tr class='border-b'><th class='text-left p-2'>Name</th><th class='text-left p-2'>Email</th><th class='text-left p-2'>Phone</th><th class='text-left p-2'>Course</th><th class='text-left p-2'>Intake</th><th class='text-left p-2'>Date</th><th class='text-left p-2'>Status</th><th class='text-center p-2'>Action</th></tr></thead><tbody>
<?php if($enrolls): foreach($enrolls as $r): ?>
<tr class='border-b'><td class='p-2 font-semibold'><?php echo htmlspecialchars($r['name']); ?></td><td class='p-2'><a class='text-blue-600' href='mailto:<?php echo htmlspecialchars($r['email']); ?>'><?php echo htmlspecialchars($r['email']); ?></a></td><td class='p-2'><?php echo htmlspecialchars($r['phone']); ?></td><td class='p-2'><?php echo htmlspecialchars($r['course'].(!empty($r['study_mode'])?' ('.$r['study_mode'].')':'')); ?></td><td class='p-2'><?php echo htmlspecialchars($r['intake_month']?:'-'); ?></td><td class='p-2'><?php echo date('M d, Y H:i',strtotime($r['submitted_at'])); ?></td><td class='p-2'><?php $sc=$r['status']==='New'?'new':($r['status']==='Reviewed'?'rev':($r['status']==='Contacted'?'con':'def')); ?><span class='pill <?php echo $sc; ?>'><?php echo htmlspecialchars($r['status']); ?></span></td><td class='p-2 text-center'><button class='text-blue-600' onclick="viewDetails('Enrollment Notes',`<?php echo addslashes(htmlspecialchars($r['notes']??'')); ?>`)">View</button><?php if(!empty($r['attachment'])): ?><div class='mt-1'><a href='<?php echo htmlspecialchars($r['attachment']); ?>' target='_blank' class='text-emerald-600 text-xs font-semibold hover:underline'>View Attachment</a></div><?php endif; ?><form method='post' action='update_status.php' class='mt-1'><input type='hidden' name='id' value='<?php echo (int)$r['id']; ?>'><input type='hidden' name='record_type' value='enrollment'><input type='hidden' name='return_query' value='<?php echo htmlspecialchars($_SERVER['QUERY_STRING']??''); ?>'><select name='status' onchange='this.form.submit()' class='border rounded px-2 py-1'><option value='New' <?php echo $r['status']==='New'?'selected':''; ?>>New</option><option value='Reviewed' <?php echo $r['status']==='Reviewed'?'selected':''; ?>>Reviewed</option><option value='Contacted' <?php echo $r['status']==='Contacted'?'selected':''; ?>>Contacted</option></select></form></td></tr>
<?php endforeach; else: ?><tr><td colspan='8' class='p-4 text-center text-slate-500'>No enrollments found.</td></tr><?php endif; ?>
</tbody></table></div>
<?php $ep='?'.http_build_query(array_merge($base,['e_page'=>max(1,$e_page-1)])); $en='?'.http_build_query(array_merge($base,['e_page'=>min($e_pages,$e_page+1)])); ?>
<div class='mt-3 flex justify-between items-center'><small>Showing <?php echo $e_total?($e_off+1):0; ?> - <?php echo min($e_off+$e_pp,$e_total); ?> of <?php echo $e_total; ?></small><div class='flex gap-2'><a class='px-3 py-1 border rounded <?php echo $e_page<=1?'opacity-40 pointer-events-none':''; ?>' href='<?php echo htmlspecialchars($ep); ?>'>Prev</a><a class='px-3 py-1 border rounded <?php echo $e_page>=$e_pages?'opacity-40 pointer-events-none':''; ?>' href='<?php echo htmlspecialchars($en); ?>'>Next</a></div></div>
</div>

<div class='panel p-4 mb-6'><h3 class='text-lg font-bold text-rose-700 mb-3'>Storage Cleanup</h3><form method='post' class='grid grid-cols-1 md:grid-cols-4 gap-3' onsubmit='return confirmClearAction()'><input type='hidden' name='return_query' value='<?php echo htmlspecialchars($_SERVER['QUERY_STRING']??''); ?>'><select id='clear_action' name='clear_action' class='border rounded-lg px-3 py-2 md:col-span-2'><option value='clear_reviewed'>Clear Reviewed + Contacted (All)</option><option value='clear_contacts_all'>Clear All Contacts</option><option value='clear_enrollments_all'>Clear All Enrollments</option><option value='clear_all'>Clear Everything</option></select><input id='confirm_clear' name='confirm_clear' class='border rounded-lg px-3 py-2' placeholder='Type CLEAR' required><button class='bg-rose-600 text-white rounded-lg px-4 py-2'>Run Cleanup</button></form></div>
<div class='flex gap-3 flex-wrap'><a href='index.html' class='px-4 py-2 border rounded-full bg-white'>Back</a><a href='change_password.php' class='px-4 py-2 rounded-full bg-indigo-600 text-white'>Change Password</a><a href='logout.php' class='px-4 py-2 rounded-full bg-rose-600 text-white'>Logout</a></div>
</div>

<div id='detailsModal' class='hidden fixed inset-0 bg-black/55 flex items-center justify-center p-4'><div class='bg-white rounded-xl max-w-2xl w-full'><div class='p-4 border-b flex justify-between'><h3 id='modalTitle' class='font-bold'>Details</h3><button onclick='closeModal()'><i class='fas fa-times'></i></button></div><div id='modalContent' class='p-4'></div></div></div>
<script>
function viewDetails(t,m){document.getElementById('modalTitle').textContent=t;document.getElementById('modalContent').innerHTML='<p class="whitespace-pre-wrap">'+(m||'No details')+'</p>';document.getElementById('detailsModal').classList.remove('hidden');}
function closeModal(){document.getElementById('detailsModal').classList.add('hidden');}
function confirmClearAction(){const a=document.getElementById('clear_action').value;const m={clear_reviewed:'Delete reviewed/contacted records from both tables?',clear_contacts_all:'Delete all contacts?',clear_enrollments_all:'Delete all enrollments?',clear_all:'Delete everything?'};return confirm(m[a]||'Proceed?');}
document.getElementById('detailsModal')?.addEventListener('click',function(e){if(e.target===this)closeModal();});
(function(){const k='admin_dark_mode',b=document.getElementById('darkModeToggle'),l=document.getElementById('darkModeLabel'),i=b?b.querySelector('i'):null;function set(x){document.body.classList.toggle('dark-mode',x);if(l)l.textContent=x?'Light':'Dark';if(i)i.className=x?'fas fa-sun':'fas fa-moon';try{localStorage.setItem(k,x?'1':'0')}catch(e){}}let s='0';try{s=localStorage.getItem(k)||'0'}catch(e){}set(s==='1'); if(b)b.addEventListener('click',()=>set(!document.body.classList.contains('dark-mode')));})();
</script></body></html>
<?php $conn->close(); ?>
