if("serviceWorker" in navigator)
    navigator.serviceWorker.register("service-worker.js?2").then(()=>console.log("Service Worker enregistré"));

{
    let deferredPrompt;
    //const addBtn = document.getElementById("web-app-install-button");
    //if (addBtn) {
        //addBtn.style.display = "none";
        window.addEventListener("beforeinstallprompt", (e) => {
            // Prevent Chrome 67 and earlier from automatically showing the prompt
            e.preventDefault();
            // Stash the event so it can be triggered later.
            deferredPrompt = e;
            // Update UI to notify the user they can add to home screen
            //addBtn.style.display = "block";
            
            window.promptInstallWebApp = function() {
                // hide our user interface that shows our A2HS button
                //addBtn.style.display = "none";
                return new Promise(function(resolve, reject) {
                    // Show the prompt
                    deferredPrompt.prompt();
                    // Wait for the user to respond to the prompt
                    deferredPrompt.userChoice.then((choiceResult) => {
                        if (choiceResult.outcome === 'accepted') {
                            console.log("L'utilisateur a accepté la demande d'installation d'application web(A2HS)");
                            resolve(true);
                        } else {
                            console.log("L'utilisateur a refusé la demande d'installation d'application web (A2HS)");
                            resolve(false);
                        }
                        //deferredPrompt = null;
                        //window.promptInstallWebApp = null;
                    });
                });
            };
            //addBtn.addEventListener("click", window.promptInstallWebApp);
        });
    //}
}