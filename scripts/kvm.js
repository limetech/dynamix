function clearHistory(){
	window.history.pushState('KVM', 'Title', '/KVM');
}

function toggle_id(itemID){ 

   if ((document.getElementById(itemID).style.display == 'none')) { 
      document.getElementById(itemID).style.display = 'table-row' 
      event.preventDefault()
   } else { 
      document.getElementById(itemID).style.display = 'none'; 
      event.preventDefault()
   }    
}

function toggleRows(what, val) {
	if (val == 1){
		style = 'table-row';
		style2 = 'none';}		
	else {
		style = 'none';
		style2 = 'table-row';}
	name = 'setup_'+what;
	d = document.getElementById(name);
	if (d != null)
		d.style.display = style;
	name = 'setup2_'+what;
	d = document.getElementById(name);
	if (d != null)
		d.style.display = style2;
}

function hideRows(what, val) {
	if (val == 1)
		style = 'table-row';
	else 
		style = 'none';
	name = 'setup_'+what;
	d = document.getElementById(name);
	if (d != null)
		d.style.display = style;
	name = 'setup2_'+what;
	d = document.getElementById(name);
	if (d != null)
		d.style.display = style;
	name = 'setup3_'+what;
	d = document.getElementById(name);
	if (d != null)
		d.style.display = style;
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

function showCDRom(){
    $('#cdrom_file').on('click');
    $('#cdrom_tree').show();
    $('#cdrom_tree').focus();
}

function showMedia(){
    $('#media_file').on('click');
    $('#media_tree').show();
    $('#media_tree').focus();
}

function showDrivers(){
    $('#drivers_file').on('click');
    $('#drivers_tree').show();
    $('#drivers_tree').focus();
}

function showDisk(){
    $('#disk_file').on('click');
    $('#disk_tree').show();
    $('#disk_tree').focus();
}

function showNewDisk(){
    $('#new_disk_file').on('click');
    $('#new_disk_tree').show();
    $('#new_disk_tree').focus();
}

$(function(){
   $('#cdrom_tree').fileTree(
   	{root:'/mnt/',filter:['iso','ISO'],script:'/plugins/dynamix.kvm.manager/classes/FileTree.php',multiFolder:false},
   	function(file) {$('#cdrom_file').val(file);$('#cdrom_tree').hide();$('#cdrom_form').submit();},
   	function(directory) {$('#cdrom_dir').val(directory);});
	$('#media_tree').fileTree(
		{root:'/mnt/',filter:['iso','ISO'],script:'/plugins/dynamix.kvm.manager/classes/FileTree.php',multiFolder:false},
		function(file) {$('#media_file').val(file);$('#media_tree').hide();},
		function(directory) {$('#media_dir').val(directory);});
	$('#drivers_tree').fileTree(
		{root:'/mnt/',filter:['iso','ISO'],script:'/plugins/dynamix.kvm.manager/classes/FileTree.php',multiFolder:false},
		function(file) {$('#drivers_file').val(file);$('#drivers_tree').hide();},
		function(directory) {$('#drivers_dir').val(directory);});
	$('#disk_tree').fileTree(
		{root:'/mnt/',filter:['qcow2','qcow','img'],script:'/plugins/dynamix.kvm.manager/classes/FileTree.php',multiFolder:false},
		function(file) {$('#disk_file').val(file);$('#disk_tree').hide();},
		function(directory) {$('#disk_file').val(directory);});
	$('#new_disk_tree').fileTree(
		{root:'/mnt/',filter:['.'],script:'/plugins/dynamix.kvm.manager/classes/FileTree.php',multiFolder:false},
		function(file) {$('#new_disk_file').val(file);$('#new_disk_tree').hide();},
		function(directory) {$('#new_disk_file').val(directory);});
	$('#media_file').click(showMedia);
	$('#drivers_file').click(showDrivers);
	$('#disk_file').click(showDisk);
	$('#new_disk_file').click(showNewDisk);
	$('#cdrom_file').click(showCDRom);
   $('.text').click(showInput);
   $('.input').blur(hideInput);
   $('body').click(function(event) {
    	if (!$(event.target).closest('#cdrom_file').length) {
      	  $('#cdrom_tree').hide();
    	};
    	if (!$(event.target).closest('#media_file').length) {
      	  $('#media_tree').hide();
    	};
    	if (!$(event.target).closest('#drivers_file').length) {
      	  $('#drivers_tree').hide();
    	};
    	if (!$(event.target).closest('#disk_file').length) {
      	  $('#disk_tree').hide();
    	};
    	if (!$(event.target).closest('#new_disk_file').length) {
      	  $('#new_disk_tree').hide();
    	};
	});
});
