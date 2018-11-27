var link = "{{$iframe_link}}"
var iframe = document.createElement('iframe');
iframe.frameBorder=0;
iframe.width="100%";
iframe.style.height="145vh";
iframe.id="randomid";
iframe.setAttribute("src", link);
var k = document.getElementById("eg_review_form"); 
if(k != null) k.appendChild(iframe);
