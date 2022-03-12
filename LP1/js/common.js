$(function() {
  $('a[href*=#]:not([href=#])').click(function() {
    if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') && location.hostname == this.hostname) {
      var target = $(this.hash);
      target = target.length ? target : $('[name=' + this.hash.slice(1) +']');
      if (target.length) {
        $('html,body').animate({
          scrollTop: target.offset().top
        }, 1000);
        return false;
      }
    }
  });
});



$(document).ready(
  function(){
    $("a img").hover(function(){
       $(this).fadeTo("fast", 0.7); // マウスオーバー時にmormal速度で、透明度を60%にする
    },function(){
       $(this).fadeTo("normal", 1.0); // マウスアウト時にmormal速度で、透明度を100%に戻す
    });
  });

  