href = "{{$eg_link}}/css/widget-3.css";
var head  = document.getElementsByTagName('head')[0];
var link  = document.createElement('link');
link.rel  = 'stylesheet';
link.type = 'text/css';
link.href = href;
head.appendChild(link);
var button = document.createElement("a");
button.innerHTML = '<img id="badge_pop" src="{{$eg_link}}/widget_3/images/img-top-rated1.png"><div id="mpopupBox" class="mpopup"><div class="mpopup-content"><div class="mpopup-head"><span class="popupClose">×</span></div><div class="mpopup-main" id="widgetiframe"></div></div></div>';
button.setAttribute("class", "badge_anchore");
document.body.appendChild(button);

var mpopup = document.getElementById('mpopupBox');
var mpLink = document.getElementById("badge_pop");
var close = document.getElementsByClassName("popupClose")[0];
mpLink.onmouseover = function() {
    mpopup.style.display = "block";
}
close.onclick = function() {
    mpopup.style.display = "none";
}
window.onmouseover = function(event) {
    if (event.target == mpopup) {
      //  mpopup.style.display = "none";
    }
}
var link = "{{$iframe_link}}"
var iframe = document.createElement('iframe');
iframe.frameBorder = 0;
iframe.width = "100%";
iframe.style.height = "600px";
iframe.id = "randomid";
iframe.setAttribute("src", link);
var k = document.getElementById("widgetiframe"); 
if(k != null) k.appendChild(iframe);