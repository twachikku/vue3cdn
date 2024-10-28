<?php

namespace twachikku\Vue3cdn\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


class VueController 
{
    use AuthorizesRequests, ValidatesRequests;

    var $request;
    var $user = null;
    var $phpfilepath = "vue3";
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->user = $request->user();
    }
    public function index($appid = "public", $pageid = "home", $args = [])
    {
        $ext = "";
        $p = strpos($pageid, ".");
        if ($p > 0) {
            $ext = file_ext($pageid);
            $pageid = substr($pageid, 0, $p);
        }
        if (in_array($pageid, ["_config", "_menu", "_right"])) {
            return response("Error: access denied <a href='/'>OK</a>", 505);
        }
        if ($appid == "json" || $ext==".json") {
            return $this->json($appid,$pageid);
        }
        
        $error = null;
        $files = $this->getFiles($appid, $pageid);
        $method = strtolower($this->request->getMethod());
        $pagename = str_replace("/", "_", $files['name']);
        $props = ['app' => $appid, 'id' => $files['name'], "args" => $args];
        $props['head'] = "";
        $props['foot'] = "";
        $props['title'] = config("app.name");
        $props['ext'] = $ext;
        $props['errors'] = [];
        $props['script'] = "";
        $props['method'] = $method;
        $props['zip'] = false;
        $props['vuetify'] = true;
        $props['quasar'] = true;
        //$props['req']  = request()->all();
        $errors = session("errors");
        if ($errors != null) {
            $props['errors'] = $errors->getBag("default")->all();
        }

        $page = ['name' => $pagename];

        $page['files'] = $files;
        $page['title'] = "$appid/$pageid";
        $data = [];
        $user = $this->user;
        if ($user) {
            $page['user'] = $user;
            $page['user_data'] = $user->data()->data;
        }
        $props['contentType'] = request("contentType", ($method == "post") ? 'json' : '');

        $response = null;
        ob_start();
        if (file_exists(base_path($files['header']))) {

            $response = include(base_path($files['header']));
        }
        if (file_exists(base_path($files['php']))) {
            $response = include(base_path($files['php']));
        } else {
            return response("Error: $appid/$pageid is not found. <a href='/'>OK</a>", 404);
        }
        $content = ob_get_contents();
        ob_end_clean();

        if ($error != null) {
            $props['errors'][] = $error;
        }
        if (isset($props['contentType']) && $props['contentType'] == "file") {
            return response()->download($props['file'], $props['filename']);
        }
        if (data_get($props, 'contentType') == "json") {
            if ($content != '')
                $props['console'] = $content;
            //$props['$page'] = $page;
            return $this->response_json($props);
        }
        if (isset($props['redirect'])) {
            return redirect($props['redirect']);
        }
        if ($response != null && $response instanceof Response) {
            return $response;
        }

        if (isset($files['php_left'])) {
            ob_start();
            include(base_path($files['php_left']));
            $props['content_left'] = trim(ob_get_contents());
            ob_end_clean();
            if ($props['content_left'] == "")
                unset($props['content_left']);
        }
        if (isset($files['php_right'])) {
            ob_start();
            include(base_path($files['php_right']));
            $props['content_right'] = trim(ob_get_contents());
            ob_end_clean();
            if ($props['content_right'] == "")
                unset($props['content_right']);
        }
        if (isset($props['layout'])) {
            $file = resource_path("{$this->phpfilepath}/layouts/{$props['layout']}.php");
            if (file_exists($file)) {
                ob_start();
                include($file);
                $content = ob_get_contents();
                ob_end_clean();
            }
        }
        $this->extract_template($content, $props);
        $props['page'] = $page;
        $props['data'] = $data;
        return view($this->phpfilepath."::index", $props);
    }


    function getFiles($appid, $pageid="")
    {
        global $app;
        $method = strtolower($this->request->getMethod());
        $f = [];
        $path = "resources/{$this->phpfilepath}/$appid";
        $f['name'] = strtolower("$appid/$pageid");
        if($pageid==""){
            $path = "resources/{$this->phpfilepath}";
            $f['name'] =  strtolower("$appid");
        }

        $f['header'] = "resources/{$this->phpfilepath}/$appid/_config.php";
        if ($method == 'get') {
            $f['php_left'] = "resources/{$this->phpfilepath}/$appid/_menu.php";
            $f['php_right'] = "resources/{$this->phpfilepath}/$appid/_right.php";
            $f['php'] = "resources/{$this->phpfilepath}/{$f['name']}.php";
            if (!file_exists(base_path($f['php_left']))) {
                unset($f['php_left']);
            }
            if (!file_exists(base_path($f['php_right']))) {
                unset($f['php_right']);
            }
            $r = "resources/{$this->phpfilepath}/{$f['name']}_right.php";
            if (file_exists(base_path($r))) {
                $f['php_right'] = $r;
            }

        } else {
            
            $f['php'] = "resources/{$this->phpfilepath}/{$f['name']}.$method.php";
        }
        return $f;
    }

    function extract_tag(&$props, $tag)
    {
        $props[$tag] = "";
        do {
            $text = $props['template'];
            $p1 = strpos($text, "<$tag");
            $found = false;
            if ($p1 !== false) {
                $found = true;
                $props['template'] = substr($text, 0, $p1);
                $p3 = strpos($text, ">", $p1) + 1;
                $p2 = strpos($text, "</$tag>", $p3);
                if ($tag == "head" || $tag == "foot") {
                    $p1 = $p3;
                } else {
                    $p2 = strpos($text, ">", $p2) + 1;
                }
                $props[$tag] .= substr($text, $p1, $p2 - $p1) . "\n";
                if ($tag == "head" || $tag == "foot") {
                    $p2 = strpos($text, ">", $p2) + 1;
                }
                $props['template'] .= substr($text, $p2);
            }
        } while ($found);
    }
    function extract_template($text, &$props)
    {
        $props['template'] = $text;
        $this->extract_tag($props, "head");
        $this->extract_tag($props, "foot");
        $this->extract_tag($props, "script");
        $this->extract_tag($props, "style");
        $props['template'] = trim($props['template']);
        // if ($props['template'] != "") {
        //     $html5 = new \Masterminds\HTML5();
        //     $dom = $html5->loadHTMLFragment($props['template']);
        //     $props['template'] = $html5->saveHTML($dom);
        // }
        return $props;
    }

    function response_json($props)
    {
        return response(json_zipencode($props),200,['Content-Type: application/json; charset=utf-8']);
    }
    function json($appid,$pageid)
    {
        $request = $this->request;
        $path = resource_path($appid);
        $res = [];
        $ext = file_ext($pageid);
        $found = false;
        if (file_exists(("$path/$pageid.json"))) {
            $res = file_get_json("$path/$pageid.json");
            $found=true;
        }
        if (file_exists(("$path/$pageid.php"))) {
            include("$path/$pageid.php");
            $found=true;
        }
        if($found) return $this->response_json($res);
        else return $this->file_not_found($appid,$pageid);
    }
    function file_not_found($appid,$pageid){
        // todo เพิ่มการป้องการ โจรกรรม นับจำนวนครั้งที่ โหลดผิด
        // $n = session('file_not_found',0)+1;
        // session(['file_not_found'=>$n]);
        // return response("Error: $appid/$pageid is not found.($n times) <a href='/'>OK</a>", 404);
        abort(404);
    }
}
