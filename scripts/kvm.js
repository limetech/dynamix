function clearHistory(){
	window.history.pushState('KVM', 'Title', '/KVM');
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

function showCDRom(){
    $('#cdrom_file').on('click');
    $('#cdrom_tree').show();
    $('#cdrom_tree').focus();
}

function cdromTree() {

}

$(function(){
   $('#cdrom_tree').fileTree(
   	{root:'/mnt/',filter:['iso','ISO'],script:'/plugins/dynamix.kvm.manager/classes/FileTree.php',multiFolder:false},
   	function(file) {$('#cdrom_file').val(file);$('#cdrom_tree').hide();$('#cdrom_form').submit();},
   	function(directory) {$('#cdrom_dir').val(directory);});
	$('#cdrom_file').click(showCDRom);
   $('.text').click(showInput);
   $('.input').blur(hideInput);
   $('body').click(function(event) {
    	if (!$(event.target).closest('#cdrom_file').length) {
      	  $('#cdrom_tree').hide();
    	};
	});
});
