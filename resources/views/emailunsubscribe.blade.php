<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    @if(!empty($project_name))
        <title>{{$project_name}}</title>
        @else
            <title>Unsubscribe</title>
    @endif

    <style>
        body{
            background-color: aliceblue;
        }
        .outer {
            display: table;
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
        }

        .middle {
            display: table-cell;
            vertical-align: middle;
        }

        .inner {
            margin-left: auto;
            margin-right: auto;
            max-width: 400px;
            padding: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="outer">
    <div class="middle">
        <div class="inner">
            @if(empty($error))
                @if(!empty($project_name))
                    @if(!empty($project_url))
                        <h1><strong><a target="_blank" href="//{{$project_url}}">{{$project_name}}</a></strong></h1>
                    @else
                        <h1><strong>{{$project_name}}</strong></h1>
                    @endif
                @endif

                <h2>You have been unsubscribed from this mailing list</h2>

                @if(!empty($subscribe_link))
                    <p><a href="{{$subscribe_link}}">Subscribe</a></p>
                @endif

            @else
                <p>Please try again later</p>
            @endif
        </div>
    </div>
</div>
</body>
</html>