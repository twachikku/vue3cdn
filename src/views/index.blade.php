<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('vue3::header')
    <title>{{ $title }}</title>
    <?=  $head ?>
</head>
<body class="font1 antialiased">

    <div id='loading_box' style='width:100%;text-align:center;position:absolute;z-index:9;'>
        <img src="/img/loading05.svg?v=01" width="300px">
    </div>
    <div class="vuebody" id='app' style="display:none">
        <v-app>
            <?= $template ?>
        </v-app>
    </div>
    @if ($zip === true)
    <script type="module">
        import zlib from 'https://cdn.jsdelivr.net/npm/browserify-zlib@0.2.0/+esm';
        import buffer from 'https://cdn.jsdelivr.net/npm/buffer@6.0.3/+esm'
        const d = "<?= 
        base64_encode(zlib_encode(
        json_encode(array_merge($data,['page'=>$page]), JSON_UNESCAPED_UNICODE),
        ZLIB_ENCODING_DEFLATE
    ))
    ?>";
        const u = zlib.unzipSync(buffer.Buffer.from(d, 'base64'));
        const $_page_data = JSON.parse(u.toString());
    </script>
    @else
    <script>
        const $_page_data = <?= json_encode(array_merge($data,['page'=>$page]), JSON_UNESCAPED_UNICODE) ?>;
    </script>    
    @endif
    <script>
        const $page = {
            data: {
                onload : [],
            },
            mounted() {
                window.$vueapp = this;
                for (const ev of this.onload) {
                   ev(this);
                }
                this.ready();
            },
            methods: {
                ready() { },
                $t:(x)=>x, 
            },
            watch: {},
            computed: {},
        };
        const $data = $page.data;
        const $methods = $page.methods;
        $data.notify_message = [];
    </script>
    <?= $script ?>
    <script>
        const {
            createApp
        } = Vue
        const {
            createVuetify
        } = Vuetify
        const vuetify = createVuetify()
        $page._data = $page.data;
        $page.data = () => Object.assign($_page_data,$page._data);
        const $_page_app = createApp($page);
    </script>

@if ($quasar === true)
    @include('vue3::quasar')

@endif
@if ($vuetify === true)
    @include('vue3::vuetify')
@endif
    
    <?= $foot ?>
    <?= $style ?>
    <script>
        window._locale = '{{ app()->getLocale() }}';
        setTimeout(() => {
            document.getElementById('app').style.display = '';
            document.getElementById('loading_box').style.display = 'none';
            $_page_app.use(vuetify).mount('#app')
            window.loading = false;
        }, 100);
        window._alert = function (msg) {
            $vueapp.notify(msg);
        }
    </script>
</body>

</html>