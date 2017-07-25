<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="title" content="诺曼-WEB全栈开发培训">
    <meta name="keywords" content="诺曼,WEB,全栈开发,培训,计算机,软件开发,程序员,工程师,开发,软件,教育">
    <meta name="description"
          content="内蒙古包头首家WEB全栈培训一对一面授指导，通过真实项目学习软件开发，毕业后可掌握HTML,JavaScript,PHP等web开发语言，操作linux服务器等一系列开发技能。">
    <meta http-equiv="content-type" content=″text/html; charset=utf-8″>
    <link href="{{asset('bootstrap/css/bootstrap.css')}}" rel="stylesheet">
    <link href="{{asset('bootstrap/css/bootstrap-theme.css')}}" rel="stylesheet">
    <link href="https://erpcdn.itxdl.cn/consultation/public/admin/css/sweetalert.css" rel="stylesheet">
    <script src="{{asset('bootstrap/js/jquery.js')}}"></script>
    <script src="{{asset('bootstrap/js/bootstrap.js')}}"></script>
    <script src="https://erpcdn.itxdl.cn/consultation/public/admin/js/sweet-alert.init.js"></script>
    <script src="https://erpcdn.itxdl.cn/consultation/public/admin/js/sweetalert.min.js"></script>
    <title>诺曼IT-教育培训</title>
</head>
<body>
<div class="container">
    <div class="row clearfix">
        <div class="col-md-12 column">
            <nav class="navbar navbar-default navbar-static-top navbar-inverse" role="navigation">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse"
                            data-target="#bs-example-navbar-collapse-1">
                        <span class="sr-only">切换导航</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                </div>
                <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                    <ul class="nav navbar-nav">
                        <li>
                            <a href="#"><img src="http://osltrvdhp.bkt.clouddn.com/img/1500274974_728697.png"
                                             class="img-responsive" style="width: 260px;"></a>
                        </li>
                    </ul>
                </div>
            </nav>
                {{--@include("IT.first")--}}

</div><!-- /.modal -->
<script>
    function datasubmit() {
        var name = $("#name").val();
        var phone = $("#phone").val();
        var qq = $("#qq").val();
        var wechat = $("#wechat").val();
        var email = $("#email").val();
        $.ajax({
            type: "POST",
            url: "/user",
            data: {
                "name": name,
                "phone": phone,
                "qq": qq,
                "wechat": wechat,
                "email": email,
                "_token": "{{ csrf_token() }}"
            },
            dataType: "json",
            success: function (msg) {
                if (msg['ServerNo'] == 'SN200') {
                    swal({
                            title: '您的资料已添加，稍后我会联系您！',
                            type: "success",
                            showCancelButton: false,
                            confirmButtonColor: "#DD6B55",
                            confirmButtonText: "确定"
                        },
                        function (isConfirm) {
                            if (isConfirm) {
                                window.location.reload();
                            }
                        });
                } else {
                    swal("失败", "请稍后重试！", "error");
                }
            },
            error: function () {
                swal("失败", "服务器错误！", "error");
            }
        });
    }
</script>
</body>
</html>