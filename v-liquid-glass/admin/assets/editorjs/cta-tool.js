/**
 * Custom Editor.js block for the site's CTA component (matches the public
 * `.article-cta` markup rendered by includes/functions.php::render_editorjs_block).
 * No build step — plain browser global, registered as `CtaTool` in editor-init.js.
 */
class CtaTool {
    static get toolbox() {
        return { title: 'CTA-блок', icon: '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L2 7l10 5 10-5-10-5z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>' };
    }

    constructor({ data }) {
        this.data = {
            heading: data.heading || '',
            text: data.text || '',
            buttonText: data.buttonText || 'Позвонить →',
            buttonHref: data.buttonHref || 'tel:+74996477281',
        };
        this.wrapper = null;
    }

    render() {
        const wrap = document.createElement('div');
        wrap.className = 'cta-tool-block';
        wrap.style.cssText = 'border:1px solid #e2ddd0;border-radius:10px;padding:1rem;background:#faf8f3';

        const mkInput = (placeholder, value, tag = 'input') => {
            const el = document.createElement(tag);
            el.placeholder = placeholder;
            el.value = value;
            el.style.cssText = 'width:100%;box-sizing:border-box;margin-bottom:.5rem;padding:.5rem;border:1px solid #ddd6c4;border-radius:6px;font:inherit';
            return el;
        };

        this.headingInput = mkInput('Заголовок CTA', this.data.heading);
        this.textInput = mkInput('Текст', this.data.text, 'textarea');
        this.buttonTextInput = mkInput('Текст кнопки', this.data.buttonText);
        this.buttonHrefInput = mkInput('Ссылка кнопки (tel:.../https://...)', this.data.buttonHref);

        wrap.appendChild(this.headingInput);
        wrap.appendChild(this.textInput);
        wrap.appendChild(this.buttonTextInput);
        wrap.appendChild(this.buttonHrefInput);

        this.wrapper = wrap;
        return wrap;
    }

    save() {
        return {
            heading: this.headingInput.value.trim(),
            text: this.textInput.value.trim(),
            buttonText: this.buttonTextInput.value.trim() || 'Позвонить →',
            buttonHref: this.buttonHrefInput.value.trim(),
        };
    }

    static get sanitize() {
        return { heading: {}, text: {}, buttonText: {}, buttonHref: {} };
    }
}
