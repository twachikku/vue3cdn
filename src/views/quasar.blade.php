<script type="module">
   
import 'https://cdn.jsdelivr.net/npm/quasar@2.x/dist/quasar.umd.prod.js';


function loadCSS(FILE_URL){
    let elem = document.createElement("link");
    elem.setAttribute("href", FILE_URL);
    elem.setAttribute("rel", "stylesheet");
    elem.setAttribute("type","text/css");
    document.body.appendChild(elem);
}

window.$q = Quasar;
window.Quasar = Quasar;

window.qalert = function(msg, options = { type: "negative" }) {
    var title = "";
    if (options.title) {
        title = `<div class='pa-2'>${options.title}</div>`;
        options.title = "";
    }
    var nobj = {
        message: `${title}<div class='pa-2 text-2' style='min-width:300px'>${msg}</div>`,
        position: "center",
        multiLine: true,
        html: true,
        actions: [{ label: "OK", color: "white" }],
    };
    Object.assign(nobj, options);
    Quasar.Notify.create(nobj);
}
window.qconfirm = function(title, message, html = false) {
    if (!message) {
        message = title;
        title = "Confirm";
    }
    return new Promise((resolve, reject) => {
        if (!message) reject("no message");
        Quasar.Dialog.create({
            title: title,
            message: message,
            ok: "Yes",
            cancel: "No",
            persistent: true,
            html: html,
        })
            .onOk(() => {
                resolve(true);
            })
            .onCancel(() => {
                resolve(false);
            });
    });
}

window.qprompt = function(title, message, prompt = null, html = false) {
    if (!message) {
        message = title;
        title = "Prompt";
    }
    if (prompt == null) prompt = { model: "", type: "text" };
    return new Promise((resolve, reject) => {
        if (!message) reject("no message");
        Quasar.Dialog.create({
            title: title,
            message: message,
            prompt: prompt,
            ok: "OK",
            cancel: "Cancel",
            persistent: true,
            html: html,
        })
            .onOk((data) => {
                resolve(data);
            })
            .onCancel(() => {
                resolve(prompt.model);
            });
    });
}

$methods.notify = function (msg) {
    Quasar.Notify.create(msg);
};
$methods.alert = function (msg, options = { type: "negative" }) {
    var title = "";
    if (options.title) {
        title = `<div class='pa-2'>${options.title}</div>`;
        options.title = "";
    }
    var nobj = {
        message: `${title}<div class='pa-2 text-2' style='min-width:300px'>${msg}</div>`,
        position: "center",
        multiLine: true,
        html: true,
        actions: [{ label: "Ok", color: "white" }],
    };
    Object.assign(nobj, options);
    Quasar.Notify.create(nobj);
};

$methods.confirm = function (title, message, html = false) {
    if (!message) {
        message = title;
        title = "Confirm";
    }
    return new Promise((resolve, reject) => {
        if (!message) reject("no message");
        Quasar.Dialog.create({
            title: title,
            message: message,
            ok: "Yes",
            cancel: "No",
            persistent: true,
            html: html,
        })
            .onOk(() => {
                resolve(true);
            })
            .onCancel(() => {
                resolve(false);
            });
    });
};

if($_page_app) $_page_app.use(Quasar);
</script>