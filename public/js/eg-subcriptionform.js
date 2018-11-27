function validate(evt) {
    var theEvent = evt || window.event;
    var key = theEvent.keyCode || theEvent.which;
    key = String.fromCharCode(key);
    var regex = /[0-9]|\./;
    if (!regex.test(key)) {
        theEvent.returnValue = false;
        if (theEvent.preventDefault) theEvent.preventDefault();
    }
}
if (document.getElementById('eg-PHONE')) {
    document.getElementById("eg-PHONE").maxLength = "10"
}

function submitForm(params,uniqueId) {
        document.getElementById('eg-embedded-subscribe').disabled = true
        var egEmail = document.getElementById('eg-EMAIL').value;
        if(document.getElementById('eg-FNAME')){
            var egFirst = document.getElementById('eg-FNAME').value;
        }
        if(document.getElementById('eg-LNAME')){
            var egLast = document.getElementById('eg-LNAME').value;
        }
        if(document.getElementById('eg-PHONE')){
           
            var egPhone = document.getElementById('eg-PHONE').value;
        }
        if(document.getElementById('eg-MESG')){
            var egMesg = document.getElementById('eg-MESG').value;
        }
       
        var re = /^(([^<>()\[\]\.,;:\s@\"]+(\.[^<>()\[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;

        if (egEmail) {
            if (re.test(egEmail) == true) {

                var http = new XMLHttpRequest();
                var url = params +"/api/list/subscription/"+uniqueId;
                var data = new FormData();
                data.append('email', egEmail);
                data.append('first_name', egFirst);
                data.append('last_name', egLast);
                data.append('phone', egPhone);
                data.append('message', egMesg);
                http.open("POST", url, true);
                http.onreadystatechange = function () {

                }
                http.send(data);
                setTimeout(function () {
                    document.getElementById('eg-success').style.display = "block"
                    document.getElementById("eg-success-response").innerHTML = "You have been subscribed successfully !   "

                }, 3000)
                setTimeout(function () {
                    document.getElementById('eg-success').style.display = "none"

                }, 5000)
            }else{
                setTimeout(function () {
                     document.getElementById('eg-error').style.display = "block"
                    document.getElementById("eg-error-response").innerHTML = "Please fill correct email "
                  
                }, 3000)
                setTimeout(function () {
                    document.getElementById('eg-error').style.display = "none"

                }, 5000)
               
            }

        } else {
            
              
        }

    }
