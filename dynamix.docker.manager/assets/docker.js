
function popupWithIframe( title, cmd, reload) {
  pauseEvents();

  $( "#iframe-popup" ).html('<iframe id="myIframe" frameborder="0" scrolling="yes" width="100%" height="99%"></iframe>');
  $("#iframe-popup").dialog({
    autoOpen: true,
    title: title,
    draggable: true,
    width : 800,
    height : ((screen.height/5)*4)||0,
    resizable : true,
    modal : true,
    show : {effect: 'fade' , duration: 250},
    hide : {effect: 'fade' , duration: 250},
    open: function(ev, ui){
     $('#myIframe').attr('src', cmd);
    },
    close: function( event, ui ) {
      if (reload){
        location = window.location.href;
      }
    }
 });
  $(".ui-dialog .ui-dialog-titlebar").addClass('menu');
  $(".ui-dialog .ui-dialog-content").css('padding','0');
  $(".ui-dialog .ui-dialog-title").css('text-align','center');
  $(".ui-dialog .ui-dialog-title" ).css( 'width', "100%");
  $('.ui-widget-overlay').click(function() { $("#iframe-popup").dialog("close"); });
};



function addContainer(container, template) {
  container = (typeof container === "undefined") ? false : container;
  if (container) {
    var title = 'Edit container: ' + container;
    op = "?xmlTemplate=edit:" + template;
  } else {
    var title = 'Create Container';
    op = "";
  }
  var cmd = '/plugins/dynamix.docker.manager/createDocker.php' + op;
  popupWithIframe(title, cmd, true);
};



function rmContainer(containers, images){
  var ctCmd = "";
  var imgCmd = "";
  var ctTitle = ""
  if (typeof containers === "object") {
    for (var i = 0; i < containers.length; i++) {
      ctCmd  += "/usr/bin/docker rm -f " + containers[i] + ";";
      imgCmd += "/usr/bin/docker rmi " + images[i] + ";";
      ctTitle += containers[i] + "<br>";
    };
  } else {
    ctCmd += "/usr/bin/docker rm -f " + containers + ";";
    imgCmd += "/usr/bin/docker rmi " + images + ";";
    ctTitle += containers + "<br>";
  }
  var title = 'Removing container';
  $( "#dialog-confirm" ).html(ctTitle);
  $( "#dialog-confirm" ).append( "<br><span style='color: #E80000;'>Are you sure?</span>" );
  $( "#dialog-confirm" ).dialog({
    title: title,
    resizable: false,
    width: 500,
    modal: true,
    show : {effect: 'fade' , duration: 250},
    hide : {effect: 'fade' , duration: 250},
    buttons: {
      "Just the container": function() {
        $( this ).dialog( "close" );
        var cmd = '/plugins/dynamix.docker.manager/exec.php?cmd=' + ctCmd;
        popupWithIframe(title, cmd, true);
      },
      "Container and image": function() {
        $( this ).dialog( "close" );
        var cmd = '/plugins/dynamix.docker.manager/exec.php?cmd=' + ctCmd + imgCmd;
        popupWithIframe(title, cmd, true);
      },
      Cancel: function() {
        $( this ).dialog( "close" );
        $( this ).html("");
      }
    }
  });
  $(".ui-dialog .ui-dialog-titlebar").addClass('menu');
  $(".ui-dialog .ui-dialog-title").css('text-align','center').css( 'width', "100%");
  $(".ui-dialog .ui-dialog-content").css('padding-top','15px').css('font-weight','bold');
  $(".ui-button-text").css('padding','0px 5px');
};



function updateContainer(containers){
  var ctCmd ="";
  var ctTitle = "";
  if (typeof containers === "object") {
    for (var i = 0; i < containers.length; i++) {
      ctCmd  += "&ct[]=" + containers[i];
      ctTitle += containers[i] + "<br>";
    };
  } else {
    ctCmd += "&ct[]=" + containers;
    ctTitle += containers + "<br>";
  }
  var title = 'Updating container';
  $( "#dialog-confirm" ).html(ctTitle);
  $( "#dialog-confirm" ).append( "<br><span style='color: #E80000;'>Are you sure?</span>" );
  $( "#dialog-confirm" ).dialog({
    title: title,
    resizable: false,
    width: 500,
    modal: true,
    show : {effect: 'fade' , duration: 250},
    hide : {effect: 'fade' , duration: 250},
    buttons: {
      "Just do it!": function() {
        $( this ).dialog( "close" );
        var cmd = "/plugins/dynamix.docker.manager/createDocker.php?updateContainer=true" + ctCmd;
        popupWithIframe(title, cmd, true);
      },
      Cancel: function() {
        $( this ).dialog( "close" );
      }
    }
  });
  $(".ui-dialog .ui-dialog-titlebar").addClass('menu');
  $(".ui-dialog .ui-dialog-title").css('text-align','center');
  $(".ui-dialog .ui-dialog-content").css('padding-top','15px');
  $(".ui-dialog .ui-dialog-content").css('font-weight','bold');
  $(".ui-button-text").css('padding','0px 5px');
  $( ".ui-dialog .ui-dialog-title" ).css( 'width', "100%");
}


function containerControl(container, action){
  document.getElementById("#cmdStartStop").value = "/usr/bin/docker " + action + " " + container;
  document.forms["formStartStop"].submit();
};



function containerLogs(container){
  var title = 'Log for: ' + container;
  var address = "/plugins/dynamix.docker.manager/exec.php?cmd=/usr/bin/docker logs --tail=350 " + container;
  popupWithIframe(title, address, false);
}



function rmImage(images, imageName){
  var imgCmd   = "";
  var imgTitle = "";
  if (typeof images === "object") {
    for (var i = 0; i < images.length; i++) {
      imgCmd += "/usr/bin/docker rmi " + images[i] + ";";
      imgTitle += imageName[i] + "<br>";
    };
  } else {
    imgCmd += "/usr/bin/docker rmi " + images + ";";
    imgTitle += imageName + "<br>";
  }
  var title = "Removing image";
  $( "#dialog-confirm" ).html(imgTitle);
  $( "#dialog-confirm" ).append( "<br><span style='color: #E80000;'>Are you sure?</span>" );
  $( "#dialog-confirm" ).dialog({
    title: title,
    dialogClass: "alert",
    resizable: false,
    width: 500,
    modal: true,
    show : {effect: 'fade' , duration: 250},
    hide : {effect: 'fade' , duration: 250},
    buttons: {
      "Just do it!": function() {
        $( this ).dialog( "close" );
        var cmd = '/plugins/dynamix.docker.manager/exec.php?cmd=' + imgCmd;
        popupWithIframe(title, cmd, true);
      },
      Cancel: function() {
        $( this ).dialog( "close" );
      }
    }
  });
  $(".ui-dialog .ui-dialog-titlebar").addClass('menu');
  $(".ui-dialog .ui-dialog-title").css('text-align','center');
  $(".ui-dialog .ui-dialog-content").css('padding-top','15px');
  $(".ui-dialog .ui-dialog-content").css('font-weight','bold');
  $(".ui-button-text").css('padding','0px 5px');
  $( ".ui-dialog .ui-dialog-title" ).css( 'width', "100%");
}



function autoStart(container, event){
  document.getElementsByName("container")[0].value = container;
  document.forms["formAutostart"].submit();
};



function reloadUpdate(){
  document.getElementById("#cmdStartStop").value = "/usr/local/emhttp/plugins/dynamix.docker.manager/dockerupdate.php";
  $("#refreshToggle").addClass("fa-spin");
  $("#refreshToggle").parent().css('color','#625D5D');
  document.forms["formStartStop"].submit();
};
