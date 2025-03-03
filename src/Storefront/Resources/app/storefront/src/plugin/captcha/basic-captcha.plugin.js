import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';
import ElementReplaceHelper from 'src/helper/element-replace.helper';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';

export default class BasicCaptchaPlugin extends Plugin {
    static options = {
        router: '',
        captchaRefreshIconId: '#basic-captcha-content-refresh-icon',
        captchaImageId: '#basic-captcha-content-image',
        basicCaptchaInputId: '#basic-captcha-input',
        basicCaptchaFieldId: '#basic-captcha-field',
        invalidFeedbackMessage: 'Incorrect input. Please try again.',
        formId: '',
        preCheckRoute: {},
    };

    init() {
        this._getForm();

        if (!this._form) {
            return;
        }

        window.formValidation.addErrorMessage('basicCaptcha', this.options.invalidFeedbackMessage);

        this.formPluginInstances = window.PluginManager.getPluginInstancesFromElement(this._form);
        this._httpClient = new HttpClient();
        this._onLoadBasicCaptcha();
        this._registerEvents();
    }

    /**
     * Registers the necessary event listeners.
     *
     * @private
     */
    _registerEvents() {
        const refreshCaptchaButton = this.el.querySelector(this.options.captchaRefreshIconId);
        refreshCaptchaButton.addEventListener('click', this._onLoadBasicCaptcha.bind(this));

        this._form.addEventListener('submit', this.validateCaptcha.bind(this));
    }

    /**
     * Fetches a new captcha image and replaces it within the form markup.
     * Is called by the refresh action of the user or if validation of the current captcha failed.
     *
     * @private
     */
    _onLoadBasicCaptcha() {
        const captchaImageId = this.el.querySelector(this.options.captchaImageId);
        ElementLoadingIndicatorUtil.create(captchaImageId);

        const url = `${this.options.router}?formId=${this.options.formId}`;
        this._httpClient.get(url, (response) => {
            this.formValidating = false;
            const srcEl = new DOMParser().parseFromString(response, 'text/html');
            ElementReplaceHelper.replaceElement(srcEl.querySelector(this.options.captchaImageId), captchaImageId, true);
            ElementLoadingIndicatorUtil.remove(captchaImageId);
        });
    }

    /**
     * Validates the captcha via server request.
     * It checks if the captcha value is correct in association to the form id.
     * Called on form submit.
     *
     * @return {Promise<boolean>}
     */
    async validateCaptcha(event) {
        event.preventDefault();

        const captchaValue = this.el.querySelector(this.options.basicCaptchaInputId).value;
        const data = JSON.stringify({
            formId: this.options.formId,
            shopware_basic_captcha_confirm: captchaValue,
        });

        const response = await fetch(this.options.preCheckRoute.path, {
            method: 'POST',
            body: data,
            headers: { 'Content-Type': 'application/json' },
        });

        const content = await response.json();
        const validCaptcha = !!content.session;
        const validForm = this._form.checkValidity();

        if (!validCaptcha) {
            // Captcha input will be marked as invalid.
            const captchaInput = this.el.querySelector(this.options.basicCaptchaInputId);
            window.formValidation.setFieldInvalid(captchaInput, ['basicCaptcha']);

            // Captcha code is always updated with new image if the validation failed.
            this._onLoadBasicCaptcha();

            // Remove loading indicators in the case the form uses them.
            // This event is triggering the corresponding logic in the form handler plugin.
            this._form.dispatchEvent(new CustomEvent('removeLoader'));
        }

        if (validCaptcha && validForm) {
            if (this._isCmsForm()) {
                // Compatibility with the CMS form handler plugin which does an async form submit.
                const formCmsHandlerPlugin = this.formPluginInstances.get('FormCmsHandler');
                formCmsHandlerPlugin._submitForm();
            } else {
                // Normal form submit.
                this._form.submit();
            }
        }
    }

    /**
     * Checks if the form is the CMS contact form.
     * This is used to work in association with the form CMS handler.
     *
     * @return {boolean}
     * @private
     */
    _isCmsForm() {
        return this.formPluginInstances.has('FormCmsHandler');
    }

    /**
     * tries to get the closest form
     *
     * @returns {HTMLElement|boolean}
     * @private
     */
    _getForm() {
        if (this.el && this.el.nodeName === 'FORM') {
            this._form = this.el;
        } else {
            this._form = this.el.closest('form');
        }
    }
}
