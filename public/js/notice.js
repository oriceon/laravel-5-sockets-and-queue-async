var async = {

    request: 0,
    config: {},

    init: function (config) {
        this.config = config;

        if(localStorage.getItem('token') == null) {
            this.getJWT();
        } else {
            this.websocket();
        }
    },

    getJWT: function() {
        var self = async;
        jQuery.get("/api/jwt", function (data) {
            var date = new Date;
            date.setHours(date.getHours() + 2);
            date = date.getTime();

            if (data.token) {
                localStorage.setItem('token', JSON.stringify({date: date, token: data.token}));
                self.websocket();
            }
        });
    },

    websocket: function() {
        var self = async;
        var storage = JSON.parse(localStorage.getItem('token'));

        if(storage.date > Date.now()) {
            self.request = 0;

            var conn = new WebSocket('ws://'+self.config.SOCKET_ADRESS+':'+self.config.SOCKET_PORT);

            conn.onopen = function (e) {
                conn.send(JSON.stringify({'channel': 'auth', 'token': storage.token}));
            };

            conn.onmessage = function (e) {
                data = JSON.parse(e.data);

                switch (data.channel) {
                    case 'toast':
                        toastr.info(data.message);
                    break;
                }
            };
        } else {
            self.request++;
            if(self.request < 4) {
                self.getJWT();
            }
        }
    }
}

async.init(appConfig);