import { docOn } from "./on.js";
import { get } from "./axios-wrapper.js";

docOn("alpine:init", () => {
    
    Alpine.data("slideshow", function() {
        return {
            show: false,
            showDialog: false,
            loading: true,
            slides: [],
            activeSlide: 0,
            src:["",""],
            continueLoop: false,
            timeoutId: null,
            init() {
                this.$watch('showDialog', value => {
                    if(value) {
                        this.$refs.dialog.showModal();
                    } else {
                        this.$refs.dialog.close();
                    }
                });

                // close the slideshow if the user exits fullscreen
                document.addEventListener('fullscreenchange', () => {
                    if(!document.fullscreenElement && this.show)
                        this.end();
                });

                // or they hit escape at all
                document.addEventListener("keydown", e => {
                    if(e.key === "Escape" && this.show)
                        this.end();
                });

                
            },
            start(withChildren) {
                this.$refs.dialog.close();
                this.$nextTick(() => this.$refs.mask.focus());
                document.documentElement.requestFullscreen();
                          
                // Reset
                this.activeSlide = 0;
                this.src = ["",""];
                this.loading = true;

                this.show = true;
                const data = {
                    children: withChildren ? 1 : 0
                };
                get(`/slideshow/${window.albumId}`, data, "Retrieving photo urls for slideshow")
                    .then(data => {
                        if(data.success) {
                            if(data.urls.length == 0) {
                                alert("This album doesn't have any photos.");
                                this.end();
                                return;
                            }

                            this.slides = data.urls.map(url => new Slide(url));
                            this.loop();
                        } else {
                            alert("There was an error trying to load the photos");
                            this.showDialog = false;
                        }
                    });
            },
            async loop(){
                let currentIndex = 0;
                await this.slides[currentIndex].loadImage();
                this.src[0] = this.slides[currentIndex].url;
                this.loading = false;
                this.continueLoop = true;
                while(this.continueLoop) {
                    const nextIndex = (currentIndex + 1) % this.slides.length;
                    await Promise.all([
                        this.slides[nextIndex].loadImage(),
                        this.delay(window.slideshowDuration),
                    ]);

                    if(!this.continueLoop)
                        break;

                    const inactiveSlide = 1 - this.activeSlide;
                    this.src[inactiveSlide] = this.slides[nextIndex].url;
                    this.activeSlide = inactiveSlide;
                    currentIndex++;
                }
            },
            // Wait for {duration} ms, but if triggered, fastForward() will
            // shortcut the delay
            delay(duration){
                return new Promise(resolve => {
                    this.timeoutId = setTimeout(resolve, duration);
                    this.fastForward = () => {
                        clearTimeout(this.timeoutId);
                        this.timeoutId = null;
                        resolve(); // resolve immediately
                    };
                });
            },
            // continually redefined based on new timeouts
            fastForward: null,
            end(){
                this.show = false;
                this.continueLoop = false;
                if(this.timeoutId != null){
                    clearTimeout(this.timeoutId);
                    this.timoutId = null;
                }
                this.fastForward = null;
                document.documentElement.exitFullscreen();
            }
        };
    });

    class Slide
    {
        url;
        #image = null;

        constructor(url){
            this.url = url;
        }

        /**
         * Returns a promise that resolves whet the image loads, 
         * or immediately resolves if the image is already loaded
        */
        loadImage(){
            if(this.image != null)
                return Promise.resolve();

            return new Promise((resolve, reject) => {
                this.image = new Image();
                this.image.onload = () => resolve();
                this.image.onerror = reject;
                this.image.src = this.url;
            });
        }
    }
})