$(function(){

    // Some globals. I don't extend JQuery to make it easier to switch frameworks.
    var username = "You";
    var agentname = "Agent DaVinci-003";
    var serverURI = "/chatbot/openai/getGPTs.php";
//    var serverURI = '/openai/test2.php';
    postData("/chatbot/openai/startChat.php");
    
    $(".usertext textarea").focus();
    $(".agenttext").attr("agentname", agentname);

    // Enter (without shift) on text input textarea submits the query.
    $(".usertext textarea").keypress(function (e) {
        if(e.which === 13 && !e.shiftKey) {
            e.preventDefault();
        
            $(this).parent().submit();
        }
    });    
    
//    $(".usertext").attr("action", serverURI);
//*
    $(".usertext").submit(function (e) {
        e.preventDefault();

        let usermsg = $(".usertext textarea");
        let userbox = $('<div class="usertextcopy" username="' + username + '">' +
                         usermsg.val() + '</div>');
        userbox.insertAfter(".messages div:last");
        userbox[0].scrollIntoView({behavior: "smooth"});

        // Create a new agent response text element and send it to be updated.
        let agentbox = $('<div class="agenttext" agentname="' + agentname + '"></div>');
        agentbox.insertAfter(".messages div:last");
        agentbox[0].scrollIntoView({behavior: "smooth"});
        
//        getServerResponse(agentbox[0], serverURI,
        getServerResponseChunked(agentbox[0], serverURI,
            $(e.currentTarget).serialize());
            
        usermsg.val("");
        $(".usertext textarea").focus();
    });

    // Handle server request/response and update an element with the response message
    async function getServerResponse(elem, url, data = {}) {
        // Post the data and handle the response.
        postData(url, data)
            .then((response) => response.json())
            .then((data) => {
                console.log('Success:', data);

                elem.innerHTML = data.choices[0].text;
                elem.scrollIntoView({behavior: "smooth"});
            })
            .catch((error) => {
                console.error('Error:', error);
            });
    }
        
    // Parse invalid JSON strings with special characters and turn them to valid JSON HTML strings.
    function JSONCompliantHTML(c)   {
        return c.replace(/\n/g, "<br />"); //.replace(/\r/g, "\\\\r").replace(/\t/g, "\\\\t");
    }

    // Handle server response with Web2.0 (not chunked transfer) server streams.
    async function getServerResponseChunked(elem, url, data = {}) {
        // Post the data and handle the response.
        postData(url, data)
            .then(async (response) => {
                const reader = response.body.pipeThrough(new TextDecoderStream()).getReader();

                for await (const chunk of readChunks(reader)) {
                    console.log(":::" + chunk + ":::");
                    try {
                        let parsed = JSON.parse(JSONCompliantHTML(chunk));
                        if(parsed.text) {
                            elem.innerHTML += JSON.parse(JSONCompliantHTML(chunk)).text;
                            elem.scrollIntoView({behavior: "smooth"});
                        } else if(parsed.option0)
                            window1.innerHTML = parsed.option0;
                        else if(parsed.option1)
                            window2.innerHTML = parsed.option1;
                        else if(parsed.option2)
                            window3.innerHTML = parsed.option2;
                    }
                    catch(e) {
                        console.log(e);
                        console.log(':::'+chunk+':::');
                    }
                }                
            })
            .catch((error) => {
                console.error('Error:', error);
            });        
    }

    // readChunks() reads from the provided reader and yields the results into an async iterable
    function readChunks(reader) {
        return {
            async* [Symbol.asyncIterator]() {
                let readResult = await reader.read();
                while (!readResult.done) {
                    yield readResult.value;
                    readResult = await reader.read();
                }
            },
        };
    }


    // POST method implementation:
    async function postData(url, data = {}) {
        // Default options are marked with *
        const response = await fetch(url, {
            method: 'POST', // *GET, POST, PUT, DELETE, etc.
            mode: 'cors', // no-cors, *cors, same-origin
            cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
            credentials: 'same-origin', // include, *same-origin, omit
            headers: {
                // 'Content-Type': 'application/json'
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            redirect: 'follow', // manual, *follow, error
            referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
            //duplex: 'half', // 'full' duplex is not properly implemented, so must have 'half' for request streaming (not response).
            // body: JSON.stringify(data) // for JSON  "Content-Type" header
            body: data // for urlencoded "Content-Type" header
        });

        return response; 
    }
 
});
