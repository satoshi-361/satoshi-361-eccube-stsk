var windowWidth = $(window).width();
var windowSm = 979;
if (windowWidth <= windowSm) {
    ///mobile

    //animation
    $(function() {
        $('.anim').on('inview', function(event, isInView, visiblePartX, visiblePartY) {
            if (isInView) {
                $(this).stop().addClass('move');
            }
        });
        $('.anim-fade').on('inview', function(event, isInView, visiblePartX, visiblePartY) {
            if (isInView) {
                $(this).stop().addClass('move-fade');
            }
        });
    });

} else {
    /////////////pc start

    //animation
    $(function() {
        $('.anim').on('inview', function(event, isInView, visiblePartX, visiblePartY) {
            if (isInView) {
                $(this).stop().addClass('move');
            }
        });
        $('.anim-fade').on('inview', function(event, isInView, visiblePartX, visiblePartY) {
            if (isInView) {
                $(this).stop().addClass('move-fade');
            }
        });
    });

} //pc end


//bigger
$(function() {
    $('.baselink li').biggerlink();
});



//toppage tab
$(function() {
    $("#tab li").click(function() {
        var num = $("#tab li").index(this);
        $(".content_wrap").addClass('disnon');
        $(".content_wrap").eq(num).removeClass('disnon');
        $("#tab li").removeClass('select');
        $(this).addClass('select');
    });
});

//web font kozuka
// (function(d) {
//     var config = {
//             kitId: 'uic5qxa',
//             scriptTimeout: 3000,
//             async: true
//         },
//         h = d.documentElement,
//         t = setTimeout(function() { h.className = h.className.replace(/\bwf-loading\b/g, "") + " wf-inactive"; }, config.scriptTimeout),
//         tk = d.createElement("script"),
//         f = false,
//         s = d.getElementsByTagName("script")[0],
//         a;
//     h.className += " wf-loading";
//     tk.src = 'https://use.typekit.net/' + config.kitId + '.js';
//     tk.async = true;
//     tk.onload = tk.onreadystatechange = function() {
//         a = this.readyState;
//         if (f || a && a != "complete" && a != "loaded") return;
//         f = true;
//         clearTimeout(t);
//         try { Typekit.load(config) } catch (e) {}
//     };
//     s.parentNode.insertBefore(tk, s)
// })(document);



var nav_width;

function openNav() {
    nav_width = document.getElementById("sp_nav").style.right;

    if (nav_width == "-100%") {
        // console.log(nav_width);
        document.getElementById("sp_nav").style.right = "0";
        $('body').css('overflow', 'hidden');
        $('.menu_close').css('display', 'block');

    } else {
        document.getElementById("sp_nav").style.right = "-100%";
        $('body').css('overflow', 'auto');
        $('.menu_close').css('display', 'none');


    }
}
var side_width;

function openside() {
    side_width = document.getElementById("side").style.left;
    console.log(side_width);
    if (side_width == "-100%") {
        // console.log(nav_width);
        document.getElementById("side").style.left = "0";
        document.getElementById("side").classList.add("is_active");
        document.getElementById("hover_panel").classList.add("is_active");
    } else {
        document.getElementById("side").style.left = "-100%";
        document.getElementById("side").classList.remove("is_active");
        document.getElementById("hover_panel").classList.remove("is_active");
    }
}