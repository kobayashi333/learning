<!DOCTYPE html>
<html lang="ja">
<meta charset="utf-8">
<title>Bidder入札者</title>
<style>
.container {
    width: 600px;
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
var requestUrl = 'ws://localhost:8080/13/bidder/1234';
(function($){
  var settings = {};
  var methods = {
    init : function( options ) {
      settings = $.extend({
        'uri'   : requestUrl,
        'conn'  : null,
        'message' : '#message',
        'display' : '#display'
      }, options);

      // bid event
      $('#confirm_bid').on('click', function(){
        var form = $('#send_data').serializeArray();
        var json = methods.parseJson(form);
        var data = { type : 'bid' };

        // json adjust
        data.data = json;
        data = JSON.stringify(data);

        console.log(data);
        if (data && settings['conn']) {
          settings['conn'].send(data);
          // $(this).chat('drawText',data);
        }
      });
      // modal open and close event
      $('#bid_btn').on('click', function(){
        if($('.confirm_modal_window').hasClass('disp')){
          $('.confirm_modal_window').removeClass('disp');
        }else{
          $('.confirm_modal_window').addClass('disp');
        }
      });
      // modal close event
      $('.confirm_modal_window').on('click', function(){
        $('.confirm_modal_window').removeClass('disp');
      });
      // first connection
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
      if(mes.type == 'bid'){
        var p = Number(mes.data.price).toLocaleString();
        var inner = '<span>' + p + '</span><span>' + mes.data.bid_type + '</span>';
        var box = $('<div></div>').html(inner);
        $('.box').prepend(box);
      }else if(mes.type == 'lot'){
        $('#bid_btn').attr('disabled', false);
        $('#confirm_bid').attr('disabled', false);
      }else if(mes.type == 'status'){
        switch (mes.data.status) {
          case 'ready':
            $('#bid_status').text('WAIT A MINUTE!!');
            $('#bid_btn').attr('disabled', true);
            $('#confirm_bid').attr('disabled', true);
            break;
          case 'start':
            $('#bid_status').text('PLACE BID');
            $('#bid_btn').attr('disabled', false);
            $('#confirm_bid').attr('disabled', false);
            break;
          case 'shortly':
            $('#bid_status').text('Fair Warning!!');
            break;
          case 'finish':
            $('#bid_statsu').text('NEXT BID');
            $('#bid_btn').attr('disabled', true);
            $('#confirm_bid').attr('disabled', true);
            break;
          case 'end':
            $('#bid_statsu').text('BID END');
            break;
          default:
            console.log('status parameter nothing!!');
        }
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
    'uri' : requestUrl,
    'message' : '#message',
    'display' : '#chat'
  });
});
</script>

<style type="text/css">
  .r_flex{
    display: flex;
    flex-flow: row;
    justify-content: space-around;
  }
  .container{
    box-sizing: border-box;
    height: 300px;
    width: 400px;
    display: flex;
    flex-flow: column;
    flex-direction: column;
    border: 1px solid #333333;
    padding: 20px;
  }
  .container > .head{
    display: flex;
    justify-content: space-between;
    height: 20px;
    width: 100%;
    font-weight: bold;
    border-bottom: 1px dashed #333333;
  }
  .container > .box{
    display: flex;
    flex-flow: column;
    flex-direction: column;
    height: 280px;
    width: 100%;
    overflow-y: auto;
  }
  .container > .box > div{
    box-sizing: border-box;
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    padding-right: 15px
    height: 30px;
    width: 100%;
    font-size: 15px;
    line-height: 30px;
    border-bottom: 1px solid #777777;
  }
  .container > .box > div > span:last-child{
    padding-right: 20px;
  }
  #bid_btn{
    display: none;
  }
  #bid_btn ~ #send_btn{
    border: 1px solid #777777;
    background-color: #980a0a;
    margin: 20px auto;
    color: #ffffff;
    padding: 20px 30px;
    width: 200px;
    height: 50px;
    text-align: center;
    display: flex;
    flex-flow: column;
    flex-direction: column;
    cursor: pointer;
  }
  #bid_btn ~ #send_btn > span{
    display: block;
    font-weight: bold;
    font-size: 15px;
  }
  #bid_btn ~ #send_btn > span:last-child{
    font-size: 25px;
    display: block;
  }
  #bid_btn:disabled ~ #send_btn{
    background-color: #333333;
  }
  #bid_btn:disabled ~ #send_btn > span:last-child{
    display: none;
  }
  .confirm_modal_window{
    overflow: hidden;
    position: fixed;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    background-color: rgba(50,50,50,0.75);
    width: 0;
    height: 0;
    z-index: 9999;
    transition: all .2s ease;
  }
  .confirm_modal_window.disp{
    width: 100%;
    height: 100vh;
    display: block;
  }
  .confirm_modal_window > div{
    position: absolute;
    top: 30px;
    left: 50%;
    transform: translateX(-50%);
    width: 50%;
    min-width: 300px;
    padding: 30px 50px;
    background-color: #ffffff;
    border: 2px solid #aefede;
  }
  .confirm_modal_window > div > p{
    text-align: center;
    font-size: 16px;
    font-weight: bold;
  }
  .confirm_modal_window > div > div{
    display: flex;
    justify-content: space-around;
    margin-top: 30px;
  }
  .confirm_modal_window > div > div > label{
    display: block;
    cursor: pointer;
    background-color: #333333;
    border: 2px solid #333333;
    padding: 10px 30px;
    color: #ffffff;
    font-weight: bold;
    transition: all .3s ease;
  }
  .confirm_modal_window > div > div > label:hover{
    background-color: #777777;
    color: #333333;
  }
  .confirm_modal_window > div > div > label:first-child{
    background-color: #ffffff;
    color: #333333;
  }
  .confirm_modal_window > div > div > label:first-child:hover{
    background-color: #333333;
    color: #ffffff;
  }
  .confirm_modal_window > div > div > input[type="button"]{
    display: none;
  }
  .fair_alert{
    display: none;
    color: red;
    font-weight: bold;
    font-size: 24px;
    text-align: center;
    margin-top: 20px;
    animation: fair_warning .2s linear infinite alternate;
  }
  .fair_alert.active{
    display: block;
  }
  @keyframes fair_warning {
    0%{
      opacity: 0;
    },
    50%,100%{
      opacity: 1;
    }
  }
</style>
</head>
<body>
  <div class="r_flex">
    <div>
      <p id="lot_id">LOT 1</p>
      <img id="lot_img" src="https://riding-auction.mdep.biz/img_lot/21-3526701.jpg">
    </div>
    <div>
      <p id="price"><span style="font-size:46px;">100,000</span> <span style="font-size:70%;">円</span></p>
      <div id="chat" class="container">
        <div class="head">
          <span>Price</span>
          <span>Paddle</span>
        </div>
        <div class="box"></div>
      </div>
      <input type="button" name="send_message" id="bid_btn" disabled>
      <label id="send_btn" for="bid_btn">
        <span id="bid_status">PLACE BID</span>
        <span id="bid_price">100000</span>
      </label>
      <p class="fair_alert">Fair Warning!!</p>
        <form id="send_data">
          <input id="lot_id" type="hidden" name="lot_id" value="1">
          <input id="bid_id" type="hidden" name="bid_id" value="1001">
          <input id="price" type="hidden" name="price" value="100000">
        </form>
    </div>
  </div>

  <div class="confirm_modal_window">
    <div>
      <p>入札を行いますか</p>
      <div>
        <label for="confirm_bid">BID</label>
        <input id="confirm_bid" type="button" name="confirm_bid" value="1">
        <label for="confirm_cancel">CANCEL</label>
        <input id="confirm_cancel" type="button" name="cancel_bid" value="0">
      </div>
    </div>
  </div>

</body>
</html>