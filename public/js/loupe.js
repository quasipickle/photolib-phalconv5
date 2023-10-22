import { docOnLoad, docOn, on } from "./on.js";
import { $, $$ } from "./selector.js";

let loupeManager;

docOnLoad(() => {
    loupeManager = new LoupeManager($$(".loupe-widget"));
    // $$(".loupe-widget").forEach($widget => {
    //    const loupe = new Loupe($widget);
    //    loupe.init();
    //    window.loupes.push(loupe);
    // });
});

/*
Keycodes:
17 = ctrl
32 = space
*/

docOn("keydown", evt => {
    if (evt.keyCode == 17) {
        loupeManager.enable();
        // window.loupes.forEach(loupe => loupe.enable(true));
    }
});

docOn("keyup", evt => {
    if (evt.keyCode == 17) {
        loupeManager.disable();
        // window.loupes.forEach(loupe => loupe.enable(false));
    }
});

docOn("keyup", evt => {
    if (evt.keyCode == 32) {
        loupeManager.resetZoom();
        // window.loupes.forEach(loupe => loupe.resetZoom());
    }
});

class LoupeManager {
    constructor($$collection)
    {
        this.loupes = [];
        $$collection.forEach($widget => {
            const loupe = new Loupe($widget);
            loupe.init();
            this.loupes.push(loupe);
        });
    }

    enable(){
        this.loupes.forEach(l => l.enable(true));
    }
    disable(){
        this.loupes.forEach(l => l.enable(false));
    }
    resetZoom(){
        this.loupes.forEach(loupe => loupe.resetZoom());
    }
}

class Loupe {
    constructor($widget) {
        this.$widget = $widget;
        this.widgetWidth = 0;
        this.widgetHeight = 0;
        this.loupeWidth = 0;
        this.loupeHeight = 0;
        this.$img = null;
        this.imgWidth = 0;
        this.imgHeight = 0;
        this.bigWidth = 0;
        this.bigHeight = 0;
        this.zoomLvl = 1;
        this.minZoomedDimension = 100;
        this.$loupe = null;
        this.enabled = false;
    }

    async init() {
        [this.widgetWidth, this.widgetHeight] = this.dimensions(this.$widget);
        this.$img = $(".loupe-widget__image", this.$widget);

        this.imgWidth = this.$img.width;
        this.imgHeight = this.$img.height;

        this.buildLoupe();
        await this.loadBigImg();
        this.addListeners();
    }

    buildLoupe() {
        this.$loupe = document.createElement('div');
        this.$loupe.classList.add('loupe-widget__loupe');
        this.$loupe.style.backgroundImage = `url(${this.$img.dataset.loupeSrc})`; 
        this.$widget.appendChild(this.$loupe);
        [this.loupeWidth, this.loupeHeight] = this.dimensions(this.$loupe);
    }

    async loadBigImg() {
        return new Promise((resolve, reject) => {
            let img = new Image();
            img.onload = () => {
                this.bigWidth = img.width;
                this.bigHeight = img.height;
                resolve();
            };
            img.src = this.$img.dataset.loupeSrc;
        });
    }

    addListeners() {
        on(this.$widget, "mousemove", evt => {
            if(!this.enabled)
                return;

            const positionWidth = Math.round(evt.offsetX/this.imgWidth * 1000) / 1000;
            const positionHeight = Math.round(evt.offsetY/this.imgHeight * 1000) / 1000;
            const offsetX = (this.loupeWidth * positionWidth) - (this.loupeWidth / 2);
            const offsetY = (this.loupeHeight * positionHeight) - (this.loupeHeight / 2);
            const positionPercent = `calc(${positionWidth*100}% - ${offsetX}px) calc(${positionHeight*100}% - ${offsetY}px)`;

            this.$loupe.style.backgroundPosition = positionPercent;  

            this.$loupe.style.left =  (this.widgetWidth * positionWidth) + "px";
            this.$loupe.style.top = (this.widgetHeight * positionHeight) + "px";
        });

        on(this.$widget, "mousewheel", evt => {
            if(!this.enabled)
                return;
            evt.preventDefault();
            if (evt.wheelDelta > 0 || evt.detail < 0) {
              if(this.zoomLvl < 1) {
                this.zoomLvl += 0.1;
                this.$loupe.style.backgroundSize = `${this.bigWidth * this.zoomLvl}px ${this.bigHeight * this.zoomLvl}px`;
              }
            }
            else {
                this.zoomLvl -= 0.1;
                if((this.bigWidth * this.zoomLvl > this.minZoomedDimension) && (this.bigHeight * this.zoomLvl > this.minZoomedDimension))
                    this.$loupe.style.backgroundSize = `${this.bigWidth * this.zoomLvl}px ${this.bigHeight * this.zoomLvl}px`;
                else
                    this.zoomLvl += 0.1;
            }
        });
    }

    enable(state){
        if(state == this.enabled)
            return;

        this.enabled = state;
        if(state)
        {
            [this.widgetWidth, this.widgetHeight] = this.dimensions(this.$widget);
            this.$widget.classList.add("loupe-widget--enabled");
        }
        else
            this.$widget.classList.remove("loupe-widget--enabled");
    }

    resetZoom(){
        this.zoomLvl = 1;
        this.$widget.dispatchEvent(new Event("mousewheel"));
    }

    dimensions($el) {
        const computedStyle = getComputedStyle($el);
        return [parseInt(computedStyle.width), parseInt(computedStyle.height)];
    }
}    