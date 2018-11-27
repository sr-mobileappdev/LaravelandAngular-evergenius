@extends('layouts.master')

@section('content')
    <p id="power">0</p>
@stop

@section('footer')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/2.0.3/socket.io.js"></script>
    <script>
        //var socket = io('http://localhost:3000');
        /*var socket = io('http://127.0.0.1:3000');
        socket.on('connect', function() {
            socket.emit('authentication', 'asdasdasdasdasdasdasd');
            socket.on('authenticated', function() {
                // use the socket as usual
                console.log('User is authenticated');
            });
        })
        .on('disconnect', function() {
            console.log('disconnected');
        });
    socket.on("test-channel:App\\Events\\MyEvent", function(message) {
        $('#power')
            .text(parseInt($('#power')
                .text()) + parseInt(message.data.power));
    });*/

 var socket = io('http://evergenius.localhost.com:8080');
        socket.on("chat.message", function(message){
            console.log(message);
            // increase the power everytime we load test route
            //$('#power').text(parseInt($('#power').text()) + parseInt(message.data.power));
        });


    </script>
@stop