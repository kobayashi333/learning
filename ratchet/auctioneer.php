<!DOCTYPE html>
<html lang="ja">
<meta charset="utf-8">
<title>オークショニア</title>
<style>
.container {
    width: 600px;
    height: 600px;
    overflow-y: auto;
}
.box{
    overflow:hidden;
}
.left {
    background-color: #D3D3D3;
    padding: 20px;
    margin: 5px;
    width: 300px;
    float:left;
}
.right {
    background-color: #ADFF2F;
    padding: 20px;
    margin: 5px;
    width: 300px;
    float:right;

}
</style>
<!-- <script src="http://code.jquery.com/jquery-3.5.1.min.js"></script> -->
<script src="https://code.jquery.com/jquery-2.2.4.js"></script>
<script>
var requestUrl = 'ws://localhost:8080/13/auctioneer/';
(function($){
  var settings = {};
  var methods = {
    init : function( options ) {
      settings = $.extend({
        'uri'   : requestUrl,
        'conn'  : null
      }, options);
      $(this).chat('connect');
    },
    connect : function () {
      if (settings['conn'] == null) {
        settings['conn'] = new WebSocket(settings['uri']);
        settings['conn'].onopen = methods['onOpen'];
        settings['conn'].onmessage = methods['onSend'];
        settings['conn'].onclose = methods['onClose'];
        settings['conn'].onerror = methods['onError'];
      }
    },
    onOpen : function ( event ) {
      console.log('サーバーに接続');
    },
    onSend : function (event) {
      if (event && event.data) {
        $(this).chat('drawText',event.data);
      }
    },
    onError : function(event) {
      console.log('エラー発生');
    },
    onClose : function(event) {
      console.log('サーバーと切断');
      settings['conn'] = null;
      setTimeout(methods['connect'], 1000);
    },
    drawText : function (message) {
      var mes = JSON.parse(message);
      console.log(mes);
      if(mes.type=='bid'){
        var p = Number(mes.data.price).toLocaleString();
        $('#price').text(p);
        $('#price').text(p);
      }else if(mes.type=='lot'){
        
      }
    },
    parseJson : function(data){
      returnJson = {};
      for (idx = 0; idx < data.length; idx++) {
        returnJson[data[idx].name] = data[idx].value
      }
      return returnJson;
    },
  }; // end of methods

  $.fn.chat = function( method ) {
    if ( methods[method] ) {
      return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
    } else if ( typeof method === 'object' || ! method ) {
      return methods.init.apply( this, arguments );
    } else {
      $.error( 'Method ' +  method + ' does not exist' );
    }
  } // end of function
})( jQuery );

$(function() {
  $(this).chat({
    'uri' : requestUrl
  });
});
</script>
<style type="text/css">
  #send_btn, #send_message{
    cursor: pointer;
  }
</style>
</head>
<body>
  <h1><span id="price"></span><span>円</span></h1>

  <h2>LOT : </h2>
  <img src="">
</body>
</html>