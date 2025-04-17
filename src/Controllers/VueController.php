<?php

namespace twachikku\Vue3cdn\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


class VueController extends \App\Http\Controllers\Controller
{
    var $phpfilepath = "vue3";

    var $resource_paths = [];
    var $request;

    public function __construct(Request $request)
    {
      $this->request = $request;
    }
  

    public function index($appid, $pageid = "", $args = [])
    {
        $_time_start=microtime(true);
        $request = $this->request;
        if ($pageid == "") {
            $pageid = $appid;
            $appid = "";
        }
        if ($pageid == "" || $pageid[0] == "_") {
            return response("Access denied <a href='/'>OK</a>", 505);
        }
        $ext = "";
        $p = strpos($pageid, ".");
        if ($p > 0) {
            $ext = self::file_ext($pageid);
            $pageid = substr($pageid, 0, $p);
        }
        if ($appid == "json" || $ext == ".json") {
            return $this->json($appid, $pageid);
        }
        $error = null;
        $files = $this->getFiles($appid, $pageid);
        $method = strtolower(request()->getMethod());
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
        $props['path'] = "$appid/$pageid";
        //$props['req']  = request()->all();
        $errors = session("errors");
        if ($errors != null) {
            $props['errors'] = $errors->getBag("default")->all();
        }

        $page = ['name' => $pagename];
        //$page['files'] = $files;
        $page['title'] = "$appid/$pageid";
        $data = [];

        $props['contentType'] = request("contentType", ($method == "post") ? 'json' : '');

        $response = null;
        ob_start();
         $params=$props;
         $props=[];
        if ($files['header']!==false) {
            $response = include($files['header']);
        }
        if ($files['php']!==false) {
            $response = include($files['php']);
        } else {
            return $this->file_not_found($appid, $pageid);
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
            $props['exec_time']=microtime(true)-$_time_start;
            return $this->response_json($props);
        }
        if (isset($props['redirect'])) {
            return redirect($props['redirect']);
        }
        if ($response != null && $response instanceof Response) {
            return $response;
        }
        $props=array_merge($params,$props);

        if ($files['php_left']!==false) {
            ob_start();
            include($files['php_left']);
            $props['content_left'] = trim(ob_get_contents());
            ob_end_clean();
            if ($props['content_left'] == "")
                unset($props['content_left']);
        }
        if ($files['php_right']!==false) {
            ob_start();
            include($files['php_right']);
            $props['content_right'] = trim(ob_get_contents());
            ob_end_clean();
            if ($props['content_right'] == "")
                unset($props['content_right']);
        }
        if (isset($props['layout'])) {
            $file = $this->get_layoutfile("{$props['layout']}.vue.php","{$props['layout']}.php");
            $page['layout']=$file;
            if ($file!==false) {
                if(file_exists($file)){
                    ob_start();
                    try {
                        include($file);
                        $content = ob_get_contents();
                    } catch (\Throwable $th) {
                        $content.="<br>".$file;
                        $content.="<br>".$th->getMessage();
                    }
                    ob_end_clean();
                }
            }
        }
        $this->extract_template($content, $props);
        $props['page'] = $page;
        $props['data'] = $data;
        $props['exec_time_1']=microtime(true)-$_time_start;
        return view($this->phpfilepath . "::index", $props);
    }


    function getFiles($appid, $pageid = "")
    {
        $request = request();
        $file = "$appid/$pageid";
        if ($appid == "")
            $file = $pageid;
        $method = strtolower($request->getMethod());
        $f = [];
        $f['name'] = strtolower($file);
        $f['header'] = $this->get_phpvuefile("$appid/_config.php");
        if ($method == 'get') {
            $f['php_left'] = $this->get_phpvuefile("$appid/_menu.vue.php","$appid/_menu.php");
            $f['php_right'] = $this->get_phpvuefile("$appid/_right.vue.php","$appid/_right.php");
            $f['php'] = $this->get_phpvuefile("{$f['name']}.vue.php","{$f['name']}.php");
        } else {
            $f['php'] = $this->get_phpvuefile("{$f['name']}.$method.php");
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
        return response($this->json_zipencode($props), 200, ['Content-Type: application/json; charset=utf-8']);
    }
    function json($appid, $pageid)
    {
        $request = request();
        $file = "$appid/$pageid";
        if ($appid == "")
            $file = $pageid;
        $method = strtolower($request->getMethod());

        if ($method == "get") {
            $filepath = $this->get_phpvuefile("$file.json");
            if ($filepath != false) {
                return $this->response_json(file_get_contents($filepath));
            }
        }
        $filepath = ($method == "get") ? $this->get_phpvuefile("$file.php") : $this->get_phpvuefile("$file.$method.php");
        if ($filepath != false) {
            $res = [];
            include($filepath);
            return $this->response_json($res);
        }
        return $this->file_not_found($appid, $pageid);
    }
    function file_not_found($appid, $pageid)
    {
        // todo เพิ่มการป้องการ โจรกรรม นับจำนวนครั้งที่ โหลดผิด
        // $n = session('file_not_found',0)+1;
        // session(['file_not_found'=>$n]);
        // return response("Error: $appid/$pageid is not found.($n times) <a href='/'>OK</a>", 404);
        abort(404);
    }

    static function file_ext($fname)
    {
        $p = strrpos($fname, ".");
        if ($p > 0) {
            return substr($fname, $p);
        }
        return "";
    }

    function get_filepath($filename)
    {
        $paths = [
            resource_path(),
            realpath(__DIR__ . "../resources")
        ];
        foreach ($paths as $p) {
            $f = "$p/$filename";
      
            if (file_exists($f))
                return $f;
        }
        return false;
    }
    function get_phpvuefile(...$filenames)
    {
        foreach ($filenames as $filename) {
            if ($filename[0] == "/")
                $filename = substr($filename, 1);
            $p = $this->get_filepath($this->phpfilepath . "/$filename");
            if ($p != false)
                return $p;
        }
        return false;
    }
    function get_layoutfile(...$filenames)
    {
        foreach ($filenames as $filename) {
            if ($filename[0] == "/")
                $filename = substr($filename, 1);
            $p = $this->get_filepath("layouts/$filename");
            if ($p != false)
                return $p;
        }
        return false;
    }

    static function json_zipencode($data)
    {   
        $json = (is_string($data))?$data:json_encode($data, JSON_UNESCAPED_UNICODE);
        $size1 = strlen($json);
        if ($size1 > 2024) {
            $data = base64_encode(zlib_encode($json, ZLIB_ENCODING_DEFLATE));
            $size2 = strlen($data);
            if ($size1 > $size2) {
                $json = ['zip' => true, "len" => $size1, 'data' => $data];
                return json_encode($json);
            }
        }
        return $json;
    }
}
