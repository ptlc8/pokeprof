<head>
<style>
.script-trigger {
    background-color: cornflowerblue;
	border-radius: 0.25em;
	padding: .5em 0 .5em .5em;
	display: inline-block;
	font-family: Helvetica, Arial;
}
.script-trigger .back {
    background-color: white;
	border-radius: .25em 0 0 .25em;
}
.script-function {
    background-color: red;
	border-radius: .25em;
	margin: 0.02em;
    padding: 0 0.2em;
}
.script-function > .name {
    vertical-align: middle;
}
.script-field {
    display: inline-block;
	background-color: white;
	min-height: .8em;
	min-width: 1.5em;
	vertical-align: middle;
	margin-left: .25em;
	cursor:pointer;
}
.script-add-function {
    font-size: .5em;
	text-decoration: underline;
	cursor: pointer;
}
</style>
<script src="/utils.js"></script>
</head>
<body style="font-size:1.8em;">
    <div id="script" onclick="onClickScript(event)">
        <div class="script-trigger" data-name="onturn">
            <span class="name">Chaque tour</span>
    	    <div class="back">
    	        <div class="script-function" data-name="attack">
    	            <span class="name">Attaquer</span>
    	            <div class="script-field" data-type="fighters"></div>
    	            <div class="script-field" data-type="number"></div>
    	        </div>
        	    <div class="script-function" data-name="heal">
        	        <span class="name">Soigner</span>
        	    </div>
    	    </div>
    	    <div class="script-add-function">+ Ajouter...</div>
        </div>
    </div>
    <script>
        var functions = {"attack":{name:"Attaquer",args:["fighters","number"]},"heal":{name:"Soigner",args:["fighters","number"]}}; 
        var triggers = {"onturn":{"name":"À chaque tour"}};
        var script = {"trigger":"onturn","functions":[{"name":"attack","args":["it[pv<50]","40"]}],"condition":"targetsleep"};
        function refreshScript(script) {
            var scriptDiv = document.getElementById("script");
            scriptDiv.appendChild(createElement("div", {className:"script-trigger",dataset:{name:script.trigger}}, [
                createElement("span", {className:"name"}, triggers[script.trigger].name),
                createElement("div", {className:"back"}, script.functions.map(function (f){
                    return createElement("div", {className:"script-function",dataset:{name:f.name}}, [
                        createElement("span", {className:"name"}, functions[f.name].name)
                    ].concat(functions[f.name].args.map(function (argType){
                        return createElement("div", {className:"script-field",dataset:{type:argType}})
                    })))
                })),
                createElement("div", {className:"script-add-function"}, "+ Ajouter...")
            ]));
        }
        async function onClickScript(event) {
            var target = event.target;
            if (target.classList.contains("script-field")) {
                switch(target.dataset.type) {
                    case "number":
                        await queryNumber();
                        break;
                    default:
                        console.error("[PokéScript] Type de champ inconnu : "+target.dataset.type);
                }
            } else if (target.classList.contains("script-add-function")) {
                await queryFunction();
            }
        }
        async function queryNumber() {
            
        }
        async function queryFunction() {
            
        }
        function compileScript(script) {
            return script.trigger+"{"+script.functions.map(f=>f.name+"("+f.args.join(",")+")"+(f.condition?"["+f.condition+"]":"")).join(" ")+"}"+(script.condition?"["+script.condition+"]":"");
        }
    </script>
</body>