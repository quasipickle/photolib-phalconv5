/* global Alpine */

import { docOn } from "./on.js";

const types = {
    ERROR: {
        css: "toast-danger"
    },
    WARNING: {
        css: "toast-warning"
    },
    INFO: {
        css: "toast-info"
    },
    SUCCESS: {
        css: "toast-success"
    }
};

docOn("alpine:init", () => {
    Alpine.store("toasts", {
        duration: 1000,//ms
        toasts: [],
        timeouts: [],
        lastId: 0,
        addError(content, title, persistent, indeterminant) {
            return this.add(types.ERROR, content, title, persistent, indeterminant);
        },
        addWarning(content, title, persistent, indeterminant) {
            return this.add(types.WARNING, content, title, persistent, indeterminant);
        },
        addInfo(content, title, persistent, indeterminant) {
            return this.add(types.INFO, content, title, persistent, indeterminant);
        },
        addSuccess(content, title, persistent, indeterminant) {
            return this.add(types.SUCCESS, content, title, persistent, indeterminant);
        },
        add(type, content, title, persistent, indeterminant) {
            const toast = Alpine.reactive({
                visible:true,
                id: ++this.lastId,
                css: type.css,
                content: content,
                title: title ?? null,
                persistent: persistent ?? false,
                indeterminant: indeterminant ?? false
            });

            this.toasts.push(toast);
            this.maybeAffectTimeout(toast);

            return toast;
        },
        update(toast, type, content, title, persistent, indeterminant) {
            toast.css = type.css;
            toast.content = content;
            toast.title = title ?? null;
            toast.persistent = persistent ?? false;
            toast.indeterminant = indeterminant ?? false;

            this.maybeAffectTimeout(toast);
        },
        hide(toast){
            toast.visible = false;
        },
        remove(toast){
            this.toasts = this.toasts.filter(i => i.id !== toast.id);
        },
        maybeAffectTimeout(toast)
        {
            if(!(toast.persistent || toast.indeterminant) && toast.timeoutId == null) {
                this._createTimeout(toast);
            }
            if(toast.persistent || toast.indeterminant && toast.timeoutId != null) {
                clearTimeout(toast.timeoutId);
                toast.timeoutId = null;
            }
        },
        _createTimeout(toast) {
            toast.timeoutId = setTimeout(() => {
                if(!(toast.persistent || toast.indeterminant)) {
                    this.hide(toast);
                }
            }, this.duration);
             
        },

    });
});

const toasts = toast => {
    const store = Alpine.store("toasts");

    if(!toast) {
        return store;
    }

    return {
        setType(type) {
            toast.css = type.css;
            return this;
        },
        setTitle(title) {
            toast.title = title;
            return this;
        },
        setContent(content) {
            toast.content = content;
            return this;
        },
        setPersistent(persistent)
        {
            toast.persistent = persistent;
            store.maybeAffectTimeout(toast);
            return this;
        },
        setIndeterminant(indeterminant)
        {
            toast.indeterminant = indeterminant;
            store.maybeAffectTimeout(toast);
            return this;
        },
        remove() {
            store.remove(toast);
        }
    };
}

export { toasts, types}