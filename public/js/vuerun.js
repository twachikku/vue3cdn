import zlib from "https://cdn.jsdelivr.net/npm/browserify-zlib@0.2.0/+esm";
import buffer from "https://cdn.jsdelivr.net/npm/buffer@6.0.3/+esm";

function addDiv(id, classname = null) {
  let e = document.createElement("div");
  if (classname) e.setAttribute("class", classname);
  e.id = id;
  document.body.appendChild(e);
  return e;
}

export default {
  async vuerun(el, page_data) {
    const page = JSON.parse(
      zlib.unzipSync(buffer.Buffer.from(page_data, "base64")).toString()
    );
    if (page.title) $(document).title = page.title;
    if (page.head) {
        if (!$("#vue_head").length) addDiv("vue_head");
        $("#vue_head").html(page.head);
    }
    if (page.template) $(el).html(page.template);
    if (page.script) {
       if (!$("#vue_script").length) addDiv("vue_script");
       $("#vue_script").html(page.script);
    }
    if (page.foot) {
        if (!$("#vue_foot").length) addDiv("vue_foot");
        $("#vue_foot").html(page.foot);
    }
  },
};
