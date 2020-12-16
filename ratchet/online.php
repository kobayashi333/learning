<!DOCTYPE html>
<html lang="ja">
<meta charset="utf-8">
<title>オンライン</title>
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
<script src="http://code.jquery.com/jquery-2.2.4.js"></script>
<script>
var requestUrl = 'ws://localhost:8080/13/online';
(function($){
  var settings = {};
  var bid_data = {
    type : 'bid',
    data : {
      lot_id  : 0,
      price   : 0
    }
  };
  var lot_data = {
    type : 'lot',
    data : {
      lot_id  : 0,
      bid_id  : 0,
      price   : 0
    }
  };
  var methods = {
    init : function( options ) {
      settings = $.extend({
        'uri'   : requestUrl,
        'conn'  : null,
        'message' : '#message',
        'display' : '#display'
      }, options);
      // $(settings['message']).keypress( methods['checkEvent'] );
      $('#send_message').on('click', function(){

        var json = methods.parseJson($('#bid').serializeArray());
        json.type = 'bid';
        json = JSON.stringify(json);
        if (json && settings['conn']) {
          settings['conn'].send(json);
          $(this).chat('drawText',json,'right');
          // $(settings['message']).val('');
        }

      })
      $(this).chat('connect');
    },
    checkEvent : function ( event ) {
      if (event && event.which == 13) {
        var message = $(settings['message']).val();
        if (message && settings['conn']) {
          settings['conn'].send(message + '');
          $(this).chat('drawText',message,'right');
          $(settings['message']).val('');
        }
      }
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
      // $(this).chat('drawText','サーバに接続','left');
    },
    onSend : function (event) {
      if (event && event.data) {
        $(this).chat('drawText',event.data,'left');
      }
    },
    onError : function(event) {
      console.log('エラー発生');
      // $(this).chat('drawText','エラー発生!','left');
    },
    onClose : function(event) {
      // $(this).chat('drawText','サーバと切断','left');
      console.log('サーバーと切断');
      settings['conn'] = null;
      setTimeout(methods['connect'], 1000);
    },
    drawText : function (message, align='left') {
      if ( align === 'left' ) {
        var inner = $('<div class="left"></div>').text(message);
      } else {
        var inner = $('<div class="right"></div>').text(message);
      }
      var box = $('<div class="box"></div>').html(inner);
      $('#chat').prepend(box);
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
    'uri' : requestUrl,
    'message' : '#message',
    'display' : '#chat'
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
  <form id="bid">
    <input type="hidden" name="lot_id" value="1234">
    <input type="hidden" name="bid_id" value="0000">
    <input type="hidden" name="price" value="">
  </form>
  <form id="lot">
    <input type="hidden" name="status" value="next">
    <input type="hidden" name="lot_id" value="1234">
  </form>
  <form id="status">
    <input type="hidden" name="bid_id" value="1001">
    <input type="hidden" name="lot_id" value="1234">
    <input type="hidden" name="price" value="1000000">
  </form>
  <input type="text" id="message" size="50" />
  <label id="send_btn" for="send_message">SEND MESSAGE : </label>
  <input type="button" name="send_message" id="send_message" value="SEND MESSAGE">
  <button id="send_bid">BID</button>
  <button id="send_lot">LOT</button>
  <button id="send_status">STATUS</button>
  <div id="chat" class="container"></div>
</body>
</html>