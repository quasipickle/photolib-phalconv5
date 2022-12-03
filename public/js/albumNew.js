import { docOn } from "./on.js";
import { post } from "./axios-wrapper.js";

docOn("alpine:init", () => {
    Alpine.data("albumNew", function() {
        return {
            name: "",
            saving: false,
            create: function(enter){
                if(this.name == "") {
                    alert("The album must have a name.");
                    this.$refs.name.focus();
                    return false;
                }
                this.saving = true;

                const data = {
                    parentId: window.albumId,
                    name: this.name
                };
                post("/album/create", data, "create a new album")
                    .then(data => {
                        if (enter) {
                            window.location.href = `${window.webRootPath}/album/${data.id}`;
                        } else {
                            window.location.reload();
                        }
                    });
                
            }
        }
    });
});