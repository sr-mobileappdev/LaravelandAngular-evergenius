<!doctype html>
<html ng-app="app">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

    <link rel='stylesheet' type='text/css' href='https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic'>
    <!-- Font Awesome -->
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
    <!--Tiny Scroll-->
   <!--  <link rel="stylesheet" href="bower_components/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.min.css" type="text/css"/> -->
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
    <script type="text/javascript" src="js/pace.min.js"></script>
    <script type="text/javascript">
    XMLHttpRequest.prototype = Object.getPrototypeOf(new XMLHttpRequest);
  </script>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCrNp8Bghna_Ej48-8eGMYrjIOmSfFHpTI&libraries=places"></script>

    <script src="https://app-rsrc.getbee.io/plugin/BeePlugin.js"></script>
    <title>EverGenius - The ULTIMATE Client Automation and Retention System</title>
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <script type="text/javascript" async="" src="https://widget.intercom.io/widget/p1f55kra"></script>
    <style type="text/css">
    #egloader{opacity:0}#egloader{-webkit-transform:opacity 0.5s ease;-moz-transform:opacity 0.5s ease;-o-transform:opacity 0.5s ease;-ms-transform:opacity 0.5s ease;transform:opacity 0.5s ease}body.pace-done #egloader{opacity:1}.pace{-webkit-pointer-events:none;pointer-events:none;-webkit-user-select:none;-moz-user-select:none;user-select:none;z-index:2000;position:fixed;margin:auto;top:0;left:0;right:0;bottom:0;height:5px;width:200px;background:#fff;border:1px solid #1f3b58;overflow:hidden}.pace .pace-progress{-webkit-box-sizing:border-box;-moz-box-sizing:border-box;-ms-box-sizing:border-box;-o-box-sizing:border-box;box-sizing:border-box;-webkit-transform:translate3d(0,0,0);-moz-transform:translate3d(0,0,0);-ms-transform:translate3d(0,0,0);-o-transform:translate3d(0,0,0);transform:translate3d(0,0,0);max-width:200px;position:fixed;z-index:2000;display:block;position:absolute;top:0;right:100%;height:100%;width:100%;background:#1f3b58}.pace.pace-inactive{display:none}div.loading-image{text-align:center;margin-top:15px;    position: fixed;top: 35%;z-index: 888;left: 50%;transform: translateX(-50%);min-height: 113px;
    background: #fff;padding: 16px;}
    @media screen and (min-width:1920px){
        div.loading-image{top:43%;}
}

 @media screen and (max-width:414px){
    div.loading-image {width: 290px; }
 }


.loading-container {width: 100%;}
body.login-page{background: #1b3856;}


.preloader {
    text-align: center;
    position: fixed;
    left: 0;
    right: 0;
    top: calc(50% - 80px);
    bottom: 0;
}
.preloader .pace.pace-active{position: relative;}
    </style>
    <script type="text/javascript">
    //<![CDATA[

    Pace.on('start', function() {
    var el = document.querySelector('div.pace-active');
    var wrapper = document.createElement('div');
    wrapper.setAttribute("class", "preloader" );
    x = document.createElement("IMG");
    x.setAttribute("src", "img/loading_bulb.gif");
    x.setAttribute("alt", "Loading..");
    wrapper.appendChild(x)
    el.parentNode.insertBefore(wrapper, el);
    wrapper.appendChild(el);
});
    Pace.on("done", function(){
        $(".preloader").hide();
        $("style").append(".pace.pace-active {display: none;}");
        $('#egloader').removeAttr('id');
        document.getElementById("pageBody").style.backgroundColor="#1b3856";
    });
    Pace.on("start", function(){
        document.getElementById("pageBody").style.backgroundColor="#fff";
    });


    //]]>
    </script>
    <link rel="stylesheet" href="{!! elixir('css/final.css') !!}">
</head>
<body route-bodyclass class="pace-done"  id="pageBody" >
    <div class="loading-container">
    <!-- <div class="loading-image content-loading">
        <img src="img/loading_bulb.gif" >

    <div class="pace  pace-inactive content-loading" id="my-element">

        <div class="pace-progress" data-progress-text="100%" data-progress="99" style="transform: translate3d(100%, 0px, 0px);">

            <div class="pace-progress-inner"></div>
        </div>
        <div class="pace-activity"></div>
    </div> -->
</div>
    <div class="wrapper" id="egloader">
        <div ui-view="layout"></div>
        <div class="control-sidebar-bg"></div>
    </div>

    <script src="{!! elixir('js/final.js') !!}" ></script>
    <script src="{{url('/js/highstock.src.js')}}"></script>
    <script src="{{url('/js/highcharts-ng.js')}}"></script>
    <script src="{{url('/js/funnel.js')}}"></script>
    <?php $protocol = stripos($_SERVER['SERVER_PROTOCOL'],'http') === true ? 'http://' : 'https://'; ?>
    <script src="{{$protocol.request()->server->get('SERVER_NAME')}}:8081/socket.io/socket.io.js" ></script>
    <script> 
        if(JSON.parse(localStorage.getItem('user_data'))){
 var admin = JSON.parse(localStorage.getItem('user_data'));
   if(JSON.parse(localStorage.getItem('user_company_details'))){
 var company_name =JSON.parse(localStorage.getItem('user_company_details')).name;
            }
     window.intercomSettings = {
        app_id: "p1f55kra",
        name: admin.name+" - "+company_name,
        email:admin.email,
        "type": 'doctor Office',
        "company_name": company_name ,
        };

        var w = window;
        var ic = w.Intercom;
        if (typeof ic === "function") {
            ic('reattach_activator');
            ic('update', intercomSettings);
            } else {
            var d = document;
            var i = function() {
            i.c(arguments)
            };
            i.q = [];
            i.c = function(args) {
            i.q.push(args)
            };
            w.Intercom = i;
        }
        }

    
    </script>
    <script >
            Pace.on("start", function(){
                var innse = document.querySelector(".pace-active").innerHTML;
                $("div.paceDiv").show();
                });

            Pace.on("done", function(){
                $("div.paceDiv").hide();
            });
    </script>
</body>
</html>
