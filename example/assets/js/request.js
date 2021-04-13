let action = false;
let update = {
    "info": false,
    "info-server": false,
    "info-machine": false
};


function onLoad(host)
{
    let hooks = {
        afterResponse: [
            (request, options, response) => {
                document.getElementById('loading').style.display = "none";
                document.getElementById('loaded').style.display = "block";

                response.json().then(
                    r => {
                        buttonController(r.is_running);
                        updateStats(r);
                    }

                );
            }
        ]
    };

    request(host + "?action=info", hooks);
}

function onStart(host, updateButton = true, spinner = true, hooks = null)
{
    if (hooks === null) {
        hooks = {
            beforeRequest: [
                (request, options) => {
                    if (spinner)
                        document.getElementById('start-spinner').style.display = "inline-block";

                    if (updateButton)
                        disableButton();

                    action = true;

                    console.log("Starting...");
                }
            ],
            afterResponse: [
                (request, options, response) => {
                    if (spinner)
                        document.getElementById('start-spinner').style.display = "none";

                    action = false;

                    if (updateButton && response.ok)
                        buttonController(true);

                    if (response.ok)
                        console.log("Started");
                }
            ]
        };
    }

    request(host + "?action=start", hooks);
}

function onStop(host, updateButton = true, spinner = true, hooks = null)
{
    if (hooks === null) {
        hooks = {
            beforeRequest: [
                (request, options) => {
                    if (spinner)
                        document.getElementById('stop-spinner').style.display = "inline-block";

                    if (updateButton)
                        disableButton();

                    action = true;

                    console.log("Stopping...")
                }
            ],
            afterResponse: [
                (request, options, response) => {
                    if (spinner)
                        document.getElementById('stop-spinner').style.display = "none";

                    action = false;

                    if (updateButton && response.ok)
                        buttonController(false);

                    if(response.ok)
                        console.log("Stopped");
                }
            ]
        };
    }

    request(host + "?action=stop", hooks);
}

function onRestart(host)
{
    let hooksStart = {
        beforeRequest: [
            (request, options) => {
                console.log("Starting...");
            }
        ],
        afterResponse: [
            (request, options, response) => {
                document.getElementById('restart-spinner').style.display = "none";

                action = false;

                if (!response.ok) {
                    console.log("Bad request start");
                    return;
                }

                response.json().then(
                    r => buttonController(r.is_running)
                );

                console.log("Started");
            }
        ]
    };

    let hooks = {
        beforeRequest: [
            (request, options) => {
                document.getElementById('restart-spinner').style.display = "inline-block";
                disableButton();
                console.log("Stopping...");
                action = true
            }
        ],
        afterResponse: [
            (request, options, response) => {
                if (!response.ok) {
                    console.log("Bad request stop");
                    action = false;
                    return;
                }

                console.log("Stopped");

                onStart(host, false, false, hooksStart)
            }
        ]
    };

    onStop(host, false, false, hooks)
}

async function getInfo(type = "info")
{
    let hooks = {
        beforeRequest: [
            (request, options) => {
                console.log("Updating " + type + "...");
                update[type] = true
            }
        ],
        afterResponse: [
            (request, options, response) => {
                update[type] = false;
                console.log("Updated " + type);

                if (!response.ok)
                    return;

                response.json().then(
                    r => {
                        console.log(r);

                        if (type === "info")
                            buttonController(r.is_running)
                        updateStats(r);
                    }
                );

            }
        ]
    };

    request(host + "?action=" + type, hooks, type);
}

function onUpdate()
{
    setInterval(function(){
        if (!update['info'])
            getInfo();

        if (!update['info-server'])
            getInfo('info-server');

        if (!update['info-machine'])
            getInfo('info-machine');

    }, 101);
}

function updateStats(stats)
{
    if (stats.is_running === false)
        return;

    if (stats.type === "server") {
        document.getElementById("serv-uptime").innerText = stats.uptime;
        document.getElementById("serv-memory-usage").innerText = stats.memory_usage;
        document.getElementById("serv-cpu-usage").innerText = stats.cpu_usage;
    }

    if (stats.type === "machine") {
        document.getElementById("mach-cpu-usage").innerText = stats.cpu_usage
        document.getElementById("mach-memory-usage").innerText = stats.memory_usage;
        document.getElementById("mach-memory").innerText = stats.memory;
        document.getElementById("mach-memory-free").innerText = stats.memory_free;
    }
}

onUpdate();

function buttonController(is_running)
{
    if (action)
        return;
    
    document.getElementById('start').disabled = is_running;
    document.getElementById('restart').disabled = !is_running;
    document.getElementById('stop').disabled = !is_running;
}

function disableButton ()
{
    if (action)
        return;

    document.getElementById('start').disabled = true;
    document.getElementById('restart').disabled = true;
    document.getElementById('stop').disabled = true;
}

async function request(host, hooks = {}, loop = false, type = "") {
    let response;

    try {
        response = await ky.get(host, {
            hooks,
        }).json();
    } catch (e) {
        if (!loop)
            await request(host, hooks, true, type);
        else if (type !== "")
            update[type] = false;
    }
}