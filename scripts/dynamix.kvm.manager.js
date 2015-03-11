$(function(){
	showStatus('libvirtd');
	$('#new_tree').dblclick(hideNew);
	$('#dir_tree').dblclick(hideDir);
	$('#mediadir_tree').dblclick(hideMediaDir);
   $('.text').click(showInput);
   $('.input').blur(hideInput);
});

function clearHistory(){
	window.history.pushState('VMs', 'Title', '/VMs');
}

function vncOpen() {
  $.post('/plugins/dynamix.kvm.manager/classes/vnc.php',{cmd:'open',root:'<?=$docroot?>',file:'/usr/local/emhttp/plugins/dynamix.kvm.manager/vncconnect.vnc'},function(data) {
    window.location.href = data;
  });
}

function loadTemplateForm(val) {
  var el = $('#form_content');

  el.fadeOut('fast', function(){
    el.html('<span style="padding-left: 20px"><img src="/webGui/images/spinner.gif"> Loading...</span>').fadeIn('fast');
  });

  $.get('/plugins/dynamix.kvm.manager/templates/' + val + '.form.php', function(data) {
    if (el.is(':animated')) {
      el.stop().html(data).animate({opacity:'100'});
    } else {
      el.hide().html(data).fadeIn('fast');
    }
  });
}

function slideUpRows($tr) {
  if ($tr.is(':visible')) {
    if ($tr.find(':animated').length === 0) {
      $tr.children('td').each(function(){
        $(this)
          .stop(true, true)
          .data("paddingstate", $(this).css(["paddingTop", "paddingBottom"]))
          .animate({ paddingTop: 0, paddingBottom: 0 }, { duration: 'fast' })
          .wrapInner('<div />')
          .children()
          .slideUp("fast", function() {
            $(this).contents().unwrap();
            $tr.hide();
          });
      });
    }
  }

  return $tr;
}

function slideDownRows($tr) {
  if (!$tr.is(':visible')) {
    if ($tr.find(':animated').length === 0) {
      $tr.children('td').each(function(){
        $(this)
          .stop(true, true)
          .wrapInner('<div style="display: none"></div>')
          .animate($(this).data("paddingstate"), { duration: 'fast', start: function() { $(this).closest('tr').show(); } })
          .children('div')
          .slideDown("fast", function() {
            $(this).contents().unwrap();
          });
      });
    }
  }

  return $tr;
}

function toggleRows(what, val, what2) {
	if (val == 1) {
		//$('.'+what).show();
    slideDownRows($('.'+what));
		if (what2 != null) {
			//$('.'+what2).hide();
      slideUpRows($('.'+what2));
    }
	} else {
		//$('.'+what).hide();
    slideUpRows($('.'+what));
		if (what2 != null) {
			//$('.'+what2).show();
      slideDownRows($('.'+what2));
    }
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


function universalTreePicker() {
  var input = $(this);
  var config = input.data();
  var picker = input.next(".fileTree");

  if (picker.length === 0) {
    $(document).mouseup(function (e) {
      var container = $(".fileTree");
      if (!container.is(e.target) && container.has(e.target).length === 0) {
        container.slideUp('fast');
      }
    });

    picker = $('<div>', {'class': 'textarea fileTree'});

    input.after(picker);
  }

  if (picker.is(':visible')) {
    picker.slideUp('fast');
  } else {
    if (picker.html() == "") {
      picker.fileTree({
        root: config.pickroot,
        filter: (config.pickfilter || '').split(",")
      },
      function(file) {
        input.val(file).change();
        if (config.pickcloseonfile) {
          picker.slideUp('fast');
        }
      },
      function(folder) {
        if (config.pickfolders) {
          input.val(folder).change();
        }
      });
    }

    picker.slideDown('fast');
  }
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

function dirTree(treeID, fileID, root){
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

function hideDir(){
    $('#dir_tree').on('dblclick');
    $('#dir_tree').hide();
}

function hideMediaDir(){
    $('#mediadir_tree').on('dblclick');
    $('#mediadir_tree').hide();
}

function hideNew(){
    $('#new_tree').on('dblclick');
    $('#new_tree').hide();
}

function checkDebug(form) {
	if (form.debug.checked == false ) {
		form.DEBUG.value = "no";
	} else {
		form.DEBUG.value = "yes";
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

