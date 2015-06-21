/*
 * Default text - jQuery plugin for html5 dragging files from desktop to browser
 *
 * Author: Weixi Yen
 *
 * Email: [Firstname][Lastname]@gmail.com
 *
 * Copyright (c) 2010 Resopollution
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   http://www.github.com/weixiyen/jquery-filedrop
 *
 * Version:  0.1.0
 * Modified by Bergware for use in unRAID OS6 (June 2015)
 *
 * Features:
 *      Allows sending of extra parameters with file.
 *      Works with Firefox 3.6+
 *      Future-compliant with HTML5 spec (will work with Webkit browsers and IE9)
 *      Multi instances (Bergware)
 * Usage:
 *  See README at project homepage
 *
 */
function base64(data) {
  var lookup = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
  var size = data.length;
  var tail = size % 3;
  var output = '';
  var i, temp, length;

  function encode(index) {
    return lookup.charAt(index & 0x3F);
  }
  function quad(num) {
    return encode(num >> 18) + encode(num >> 12) + encode(num >> 6) + encode(num);
  }
  for (i = 0, length = size-tail; i<length; i+=3) {
    output += quad((data.charCodeAt(i) << 16) + (data.charCodeAt(i+1) << 8) + (data.charCodeAt(i+2)));
  }
  switch (tail) {
  case 1:
    temp = data.charCodeAt(size-1);
    output += encode(temp >> 2);
    output += encode(temp << 4);
    output += '==';
    break;
  case 2:
    temp = (data.charCodeAt(size-2) << 8) + (data.charCodeAt(size-1));
    output += encode(temp >> 10);
    output += encode(temp >> 4);
    output += encode(temp << 2);
    output += '=';
    break;
  }
  return output;
}

(function($) {
  jQuery.event.props.push('dataTransfer');
  var opts = {}, defaults = {
    url: '',
    refresh: 1000,
    paramname: 'userfile',
    maxfiles: 25,
    maxfilesize: 1024, // 1MB
    data: {},
    drop: empty,
    dragEnter: empty,
    dragOver: empty,
    dragLeave: empty,
    docEnter: empty,
    docOver: empty,
    docLeave: empty,
    beforeEach: empty,
    afterAll: empty,
    rename: empty,
    error: function(err, file, i){alert(err);},
    uploadStarted: empty,
    uploadFinished: empty,
    progressUpdated: empty,
    speedUpdated: empty
  },
  errors = ["BrowserNotSupported", "TooManyFiles", "FileTooLarge"],
  doc_leave_timer,
  stop_loop = false,
  files_count = 0,
  files;

  $.fn.filedrop = function(options) {
    opts = $.extend({}, defaults, options);
    this.bind('drop', drop).bind('dragenter', dragEnter).bind('dragover', dragOver).bind('dragleave', dragLeave);
    $(document).bind('drop', docDrop).bind('dragenter', docEnter).bind('dragover', docOver).bind('dragleave', docLeave);
  };

  function drop(e) {
    opts.drop(e);
    files = e.dataTransfer.files;
    if (files === null || files === undefined) {
      opts.error(errors[0]);
      return false;
    }
    files_count = files.length;
    upload();
    e.preventDefault();
    return false;
  }

  function getBuilder(filename, filedata) {
    var builder = [];
    $.each(opts.data, function(key, val) {
      if (typeof val === 'function') val = val();
      builder.push(key + '=' + encodeURI(val));
    });
    builder.push('filename=' + encodeURI(filename));
    builder.push('filedata=' + base64(filedata));
    return builder.join('&');
  }

  function progress(e) {
    if (e.lengthComputable) {
      var percentage = Math.round((e.loaded * 100) / e.total);
      if (this.currentProgress != percentage) {
        this.currentProgress = percentage;
        opts.progressUpdated(this.index, this.file, this.currentProgress);
        var elapsed = new Date().getTime();
        var diffTime = elapsed - this.currentStart;
        if (diffTime >= opts.refresh) {
          var diffData = e.loaded - this.startData;
          var speed = diffData / diffTime; // KB per second
          opts.speedUpdated(this.index, this.file, speed);
          this.startData = e.loaded;
          this.currentStart = elapsed;
        }
      }
    }
  }

  function upload() {
    stop_loop = false;
    if (!files) {
      opts.error(errors[0]);
      return false;
    }
    var filesDone = 0, filesRejected = 0;
    if (files_count > opts.maxfiles) {
      opts.error(errors[1]);
      return false;
    }
    for (var i=0; i<files_count; i++) {
      if (stop_loop) return false;
      try {
        if (beforeEach(files[i]) != false) {
          if (i === files_count) return;
          var reader = new FileReader(),
          max_file_size = 1024 * opts.maxfilesize;
          reader.index = i;
          if (files[i].size > max_file_size) {
            opts.error(errors[2], files[i], i);
            filesRejected++;
            continue;
          }
          reader.onloadend = send;
          reader.readAsBinaryString(files[i]);
        } else {
          filesRejected++;
        }
      } catch(err) {
        opts.error(errors[0]);
        return false;
      }
    }
    function send(e) {
      // Sometimes the index is not attached to the
      // event object. Find it by size. Hack for sure.
      if (e.target.index == undefined) {
        e.target.index = getIndexBySize(e.total);
      }
      var xhr = new XMLHttpRequest(), upload = xhr.upload, file = files[e.target.index], index = e.target.index, start_time = new Date().getTime(), builder;
      newName = rename(file.name);
      if (typeof newName === "string") {
        builder = getBuilder(newName, e.target.result);
      } else {
        builder = getBuilder(file.name, e.target.result);
      }
      upload.index = index;
      upload.file = file;
      upload.downloadStartTime = start_time;
      upload.currentStart = start_time;
      upload.currentProgress = 0;
      upload.startData = 0;
      upload.addEventListener("progress", progress, false);
      xhr.open("POST", opts.url, true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.send(builder);
      opts.uploadStarted(index, file, files_count);
      xhr.onload = function() {
        if (xhr.responseText) {
          var now = new Date().getTime(), timeDiff = now - start_time, result = opts.uploadFinished(index, file, xhr.responseText, timeDiff);
          filesDone++;
          if (filesDone == files_count - filesRejected) afterAll();
          if (result === false) stop_loop = true;
        }
      };
    }
  }

  function getIndexBySize(size) {
    for (var i=0; i<files_count; i++) {
      if (files[i].size == size) return i;
    }
    return undefined;
  }

  function rename(name) {
    return opts.rename(name);
  }
  function beforeEach(file) {
    return opts.beforeEach(file);
  }

  function afterAll() {
    return opts.afterAll();
  }

  function dragEnter(e) {
    clearTimeout(doc_leave_timer);
    e.preventDefault();
    opts.dragEnter(e);
  }

  function dragOver(e) {
    clearTimeout(doc_leave_timer);
    e.preventDefault();
    opts.docOver(e);
    opts.dragOver(e);
  }

  function dragLeave(e) {
    clearTimeout(doc_leave_timer);
    opts.dragLeave(e);
    e.stopPropagation();
  }

  function docDrop(e) {
    e.preventDefault();
    opts.docLeave(e);
    return false;
  }

  function docEnter(e) {
    clearTimeout(doc_leave_timer);
    e.preventDefault();
    opts.docEnter(e);
    return false;
  }

  function docOver(e) {
    clearTimeout(doc_leave_timer);
    e.preventDefault();
    opts.docOver(e);
    return false;
  }

  function docLeave(e) {
    doc_leave_timer = setTimeout(function(){
      opts.docLeave(e);
    }, 200);
  }

  function empty(){}

})(jQuery);