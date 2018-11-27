
        <html>
            <head>
                <title>New Instagram Account</title>
            </head>
            <body>
                <style>
                    body
                    {
                        background-color: #fafafa;    
                    }
                    form
                    {
                        width: 300px;
                        margin:auto;
                        background-color: #fff;
                        border: 1px solid #efefef;
                        padding:15px;
                    }
                    input[type="text"],input[type="password"]
                    {
                        display: block;
                        width: 100%;
                        height: 34px;
                        padding: 6px 12px;
                        font-size: 14px;
                        line-height: 1.428571429;
                        color: #555;
                        background-color: #fff;
                        background-image: none;
                        border: 1px solid #ccc;
                        border-radius: 4px;
                        -webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,.075);
                        box-shadow: inset 0 1px 1px rgba(0,0,0,.075);
                        margin-bottom:20px;    
                        background: #fafafa;
                        border: solid 1px #dbdbdb;
                    }
                    button
                    {
                        color: #fff;
                        background-color: #5cb85c;
                        border-color: #4cae4c;
                        display: inline-block;
                        margin-bottom: 0;
                        font-weight: 400;
                        text-align: center;
                        vertical-align: middle;
                        cursor: pointer;
                        background-image: none;
                        border: 1px solid transparent;
                        white-space: nowrap;
                        padding: 6px 12px;
                        font-size: 14px;
                        line-height: 1.428571429;
                        border-radius: 4px;    
                    }
                </style>

                <form action="{{ $action_url }}" method="post" accept-charset="utf-8">
                <div class="control-group">
                    <label class="control-label" for="user">Username:</label>
                    <div class="controls">
                        <input required="" id="user" name="username" type="text" class="form-control" placeholder="Instagram Username(No Email)">
                    </div>
                </div>
                <div class="control-group">

                    <label class="control-label" for="password">Password:</label>
                    <div class="controls">
                        <input required="" id="password" name="password" class="form-control" type="password" placeholder="********">
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" for="signin"></label>
                    <div class="controls">
                        <button id="signin" name="signin" class="btn btn-success">Save Account</button>
                    </div>
                </div>
                </form>            </body>
        </html>
        