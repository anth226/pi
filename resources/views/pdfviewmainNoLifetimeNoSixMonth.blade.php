<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <title>{{$file_name}}</title>
    <style>
        @font-face {
            font-family: 'Poppins-Regular';
            font-style: normal;
            font-weight: 300;
            src: url(/fonts/Poppins-Regular.ttf) format('truetype');
            }
        @font-face {
            font-family: 'Poppins-Light';
            font-style: normal;
            font-weight: normal;
            src: url(/fonts/Poppins-Light.ttf) format('truetype');
        }
        @font-face {
            font-family: 'Poppins-Bold';
            font-style: normal;
            font-weight: 900;
            src: url(/fonts/Poppins-Bold.ttf) format('truetype');
            }
    </style>
</head>
<body style="background-color: white;padding:4%;font-family: 'Poppins-Regular', sans-serif;font-size: 15px;margin: 0 auto;">
    @include('pdfviewNoLifetimeNoSixMonth')
    {!! $pdf_footer !!}
</body>
</html>