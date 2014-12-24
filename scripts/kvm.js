function clearHistory(){
	window.history.pushState('KVM', 'Title', '/KVM');
}

function toggleTab(val) {
	if (val == 'q35') {
		document.getElementById('usbtab').checked = false;
	} else {
		document.getElementById('usbtab').checked = true;
	}
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

function showNew(){
    $('#new_file').on('click');
    $('#new_tree').show();
    $('#new_tree').focus();
}

function showDir(){
    $('#dir_file').on('click');
    $('#dir_tree').show();
    $('#dir_tree').focus();
}

function showMediaDir(){
    $('#mediadir_file').on('click');
    $('#mediadir_tree').show();
    $('#mediadir_tree').focus();
}

$(function(){
   $('#cdrom_tree').fileTree(
   	{root:'/mnt/',filter:['iso','ISO'],script:'/plugins/dynamix.kvm.manager/classes/FileTree.php',multiFolder:false},
   	function(file) {$('#cdrom_file').val(file);$('#cdrom_tree').hide();$('#cdrom_form').submit();},
   	function(directory) {$('#cdrom_dir').val(directory);});
	$('#drivers_tree').fileTree(
		{root:'/mnt/',filter:['iso','ISO'],script:'/plugins/dynamix.kvm.manager/classes/FileTree.php',multiFolder:false},
		function(file) {$('#drivers_file').val(file);$('#drivers_tree').hide();},
		function(directory) {$('#drivers_dir').val(directory);});
	$('#media_file').click(showMedia);
	$('#drivers_file').click(showDrivers);
	$('#disk_file').click(showDisk);
	$('#new_file').click(showNew);
	$('#cdrom_file').click(showCDRom);
	$('#dir_file').click(showDir);
	$('#mediadir_file').click(showMediaDir);
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
    	if (!$(event.target).closest('#new_file').length) {
      	  $('#new_tree').hide();
    	};
    	if (!$(event.target).closest('#dir_file').length) {
      	  $('#dir_tree').hide();
    	};
    	if (!$(event.target).closest('#mediadir_file').length) {
      	  $('#mediadir_tree').hide();
    	};
	});
});

function checkDebug(form) {
	if (form.error.checked == false ) {
		form.DEBUG.value = "disable";
	} else {
		form.DEBUG.value = "enable";
	}
}

function checkENABLE(form) {
	if (form.enable.checked == false ) {
		form.SERVICE.value = "disable";
	} else {
		form.SERVICE.value = "enable";
	}
}

function verifyDATA(form) {
	if (form.mediadir_file.value == ""){
		form.mediadir_file.value = "/mnt/";
	}
	if (form.dir_file.value == ""){
		form.dir_file.value = "/mnt/";
	}
	form.mediadir_file.value = form.mediadir_file.value.replace(/ /g,"_");
	form.dir_file.value = form.dir_file.value.replace(/ /g,"_");
}

