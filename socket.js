require('dotenv').config();

var sys_env = process.env.APP_ENV;
/* Local Code */
if (sys_env != 'production') {
    var app = require('express')(),
        http = require('http').Server(app),
        io = require('socket.io')(http),
        Redis = require('ioredis'),
        redis = new Redis();
    var port = 8081,
        users = nicknames = {};
    redis.subscribe(['chat.message', 'incoming.sms'], function (err, count) {

    });
    redis.on('message', function (channel, message) {
        console.log('Receive message %s from system in channel %s', message, channel);
        io.emit(channel, message);
    });
    http.listen(port, function () {
        console.log('Listening on *:' + port);
    });
} else {
    /* Live Code */
    var fs = require('fs');
    var app = require('express')();
    var https = require('https');
    var server = https.createServer({
        key: fs.readFileSync('./public/ssl/private.key'),
        cert: fs.readFileSync('./public/ssl/certificate.crt'),
        ca: fs.readFileSync('./public/ssl/certificate-ca.crt'),
        requestCert: false,
        rejectUnauthorized: false
    }, app);

    var port = 8081,
        users = nicknames = {};

    var server = server.listen(port, function () {
        console.log('Listening on *:' + port);
    });

    io = require('socket.io')(server, { wsEngine: 'ws' }),
        Redis = require('ioredis'),
        redis = new Redis();


    redis.subscribe(['chat.message', 'incoming.sms'], function (err, count) {

    });

    redis.on('message', function (channel, message) {
        console.log('Receive message %s from system in channel %s', message, channel);
        io.emit(channel, message);
    });
}