function clearHistory(){
	window.history.pushState('KVM', 'Title', '/KVM');
}

function vncOpen() {
  $.post('/plugins/dynamix.kvm.manager/classes/vnc.php',{cmd:'open',root:'<?=$docroot?>',file:'/usr/local/emhttp/plugins/dynamix.kvm.manager/vncconnect.vnc'},function(data) {
    window.location.href = data;
  });
}

function toggle_id(itemID){ 
   if ((document.getElementById(itemID).style.display == 'none')) { 
      document.getElementById(itemID).style.display = 'table-row';
      event.preventDefault();
   } else { 
      document.getElementById(itemID).style.display = 'none'; 
      event.preventDefault();
   }    
}

function showInput(){
    $(this).off('click');
    $(this).siblings('input').each(function(){$(this).show();});
    $(this).siblings('input').focus();
    $(this).hide();
}

function hideInput(){
    $(this).hide();
    $(this).siblings('span').show();
    $(this).siblings('span').click(showInput);
}

function CDRomTree(cdrom_treeID, cdrom_fileID, cdrom_formID){
   $(cdrom_treeID).fileTree(
   	{root:'/mnt/',filter:['iso','ISO'],script:'/plugins/dynamix.kvm.manager/classes/jqueryFileTree.php',multiFolder:false},
   	function(file) {$(cdrom_fileID).val(file);$(cdrom_treeID).hide();$(cdrom_formID).submit();},
   	function(directory) {$('.cdrom_dir').val(directory);});
	$(cdrom_treeID).show();
   $(cdrom_treeID).focus();
   $('body').click(function(event) {
    	if (!$(event.target).closest(cdrom_fileID).length) {
      	  $(cdrom_treeID).hide();
    	};
	});
}

function mediaTree(treeID, fileID, root){
   $(treeID).fileTree(
   	{root:''+root,filter:['iso','ISO'],script:'/plugins/dynamix.kvm.manager/classes/jqueryFileTree.php',multiFolder:false},
   	function(file) {$(fileID).val(file);$(treeID).hide();},
   	function(directory) {$('.disk_').val(directory);});
	$(treeID).show();
   $(treeID).focus();
   $('body').click(function(event) {
    	if (!$(event.target).closest(fileID).length) {
      	  $(treeID).hide();
    	};
	});
}

function diskTree(treeID, fileID, root){
   $(treeID).fileTree(
   	{root:''+root,filter:['qcow2','qcow','img','QCOW2','QCOW','IMG'],script:'/plugins/dynamix.kvm.manager/classes/jqueryFileTree.php',multiFolder:false},
   	function(file) {$(fileID).val(file);$(treeID).hide();},
   	function(directory) {$('.disk_dir').val(directory);});
	$(treeID).show();
   $(treeID).focus();
   $('body').click(function(event) {
    	if (!$(event.target).closest(fileID).length) {
      	  $(treeID).hide();
    	};
	});
}

function newTree(treeID, fileID, root){
   $(treeID).fileTree(
   	{root:''+root,filter:['.'],script:'/plugins/dynamix.kvm.manager/classes/jqueryFileTree.php',multiFolder:false},
   	function(file) {$(fileID).val(file);$(treeID).hide();},
   	function(directory) {$(fileID).val(directory);});
	$(treeID).show();
   $(treeID).focus();
   $('body').click(function(event) {
    	if (!$(event.target).closest(fileID).length) {
      	  $(treeID).hide();
    	};
	});
}
function toggleRows(what, val, what2) {
	if (val == 1){
		$('.'+what).show();
		if (what2 != null)
			$('.'+what2).hide();
	} else {
		$('.'+what).hide();
		if (what2 != null)
			$('.'+what2).show();
	}		
}

function hideNew(){
    $('#new_tree').on('dblclick');
    $('#new_tree').hide();
}

$(function(){
   $('.text').click(showInput);
   $('.input').blur(hideInput);
});