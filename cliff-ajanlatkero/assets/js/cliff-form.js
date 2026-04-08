/**
 * Cliff Ajánlatkérő Wizard - JavaScript
 */
(function () {
    'use strict';

    const wizard = {
        el: null,
        form: null,
        steps: [],
        navItems: [],
        currentStep: 0,
        totalSteps: 0,
        formData: {},

        init() {
            this.el = document.getElementById('cliff-wizard');
            if (!this.el) return;

            this.form = document.getElementById('cliff-form');
            this.steps = Array.from(this.el.querySelectorAll('.cliff-step'));
            this.navItems = Array.from(this.el.querySelectorAll('.cliff-nav-item'));
            this.totalSteps = this.steps.length - 1; // Exclude thank you step

            this.bindEvents();
            this.updateNav();
        },

        bindEvents() {
            // "Next" buttons
            this.el.querySelectorAll('.cliff-btn-next').forEach(btn => {
                btn.addEventListener('click', () => {
                    const next = parseInt(btn.dataset.next, 10);
                    if (this.validateStep(this.currentStep)) {
                        this.goToStep(next);
                    }
                });
            });

            // Radio card selections (auto-advance for single-select steps)
            this.el.querySelectorAll('.cliff-card:not(.cliff-card-multi) input[type="radio"]').forEach(input => {
                input.addEventListener('change', () => {
                    const step = input.closest('.cliff-step');
                    const stepIndex = parseInt(step.dataset.step, 10);
                    // Small delay for visual feedback
                    setTimeout(() => {
                        this.goToStep(stepIndex + 1);
                    }, 400);
                });
            });

            // Nav item clicks (go back to completed steps)
            this.navItems.forEach(item => {
                item.addEventListener('click', () => {
                    const stepIndex = parseInt(item.dataset.step, 10);
                    if (item.classList.contains('is-completed') || item.classList.contains('is-unlocked')) {
                        this.goToStep(stepIndex);
                    }
                });
            });

            // File upload labels
            const alaprajz = document.getElementById('cliff-alaprajz');
            const foto = document.getElementById('cliff-foto');

            if (alaprajz) {
                alaprajz.addEventListener('change', () => {
                    this.handleFileSelect(alaprajz, 'cliff-alaprajz-text');
                });
            }

            if (foto) {
                foto.addEventListener('change', () => {
                    this.handleFileSelect(foto, 'cliff-foto-text');
                });
            }

            // Submit button
            const submitBtn = document.getElementById('cliff-submit');
            if (submitBtn) {
                submitBtn.addEventListener('click', () => this.submitForm());
            }

            // Email input - enter key
            const emailInput = document.getElementById('cliff-email');
            if (emailInput) {
                emailInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (this.validateStep(0)) {
                            this.goToStep(1);
                        }
                    }
                });
            }
        },

        handleFileSelect(input, textId) {
            const textEl = document.getElementById(textId);
            const btn = input.closest('.cliff-upload-btn');
            if (input.files.length > 0) {
                textEl.textContent = input.files[0].name;
                btn.classList.add('has-file');
            } else {
                textEl.textContent = 'fájl kiválasztása';
                btn.classList.remove('has-file');
            }
        },

        validateStep(stepIndex) {
            switch (stepIndex) {
                case 0:
                    return this.validateEmail();
                case 7:
                    return this.validateContact();
                default:
                    return true;
            }
        },

        validateEmail() {
            const input = document.getElementById('cliff-email');
            const error = document.getElementById('cliff-email-error');
            const value = input.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!value) {
                input.classList.add('is-error');
                error.textContent = 'Kérjük adja meg az e-mail címét!';
                input.focus();
                return false;
            }

            if (!emailRegex.test(value)) {
                input.classList.add('is-error');
                error.textContent = 'Az e-mail cím formátuma nem megfelelő!';
                input.focus();
                return false;
            }

            input.classList.remove('is-error');
            error.textContent = '';
            return true;
        },

        validateContact() {
            const privacy = this.form.querySelector('input[name="adatvedelem"]');
            if (!privacy.checked) {
                alert('Kérjük fogadja el az Adatvédelmi tájékoztatót!');
                return false;
            }
            return true;
        },

        goToStep(index) {
            if (index < 0 || index >= this.steps.length) return;

            // Mark current step data
            this.collectStepData(this.currentStep);

            // Hide current step
            this.steps[this.currentStep].classList.remove('is-active');

            // Mark steps as completed
            if (index > this.currentStep) {
                for (let i = this.currentStep; i < index; i++) {
                    this.navItems[i]?.classList.add('is-completed');
                    this.navItems[i]?.classList.remove('is-active');
                }
            }

            // Show new step
            this.currentStep = index;
            this.steps[index].classList.remove('is-active');
            // Force reflow for animation
            void this.steps[index].offsetWidth;
            this.steps[index].classList.add('is-active');

            this.updateNav();
            this.scrollToTop();
        },

        updateNav() {
            this.navItems.forEach((item, i) => {
                item.classList.remove('is-active', 'is-unlocked');

                if (i === this.currentStep) {
                    item.classList.add('is-active');
                } else if (i < this.currentStep && !item.classList.contains('is-completed')) {
                    item.classList.add('is-completed');
                } else if (i > this.currentStep && item.classList.contains('is-completed')) {
                    // Already completed, keep it
                } else if (i <= this.currentStep) {
                    item.classList.add('is-unlocked');
                }
            });

            // Update progress bar
            const progressBar = this.el.querySelector('.cliff-progress-bar');
            if (progressBar) {
                const pct = (this.currentStep / this.totalSteps) * 100;
                progressBar.style.width = pct + '%';
            }

            // Scroll nav item into view on mobile
            const activeNav = this.navItems[this.currentStep];
            if (activeNav) {
                activeNav.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }
        },

        scrollToTop() {
            const rect = this.el.getBoundingClientRect();
            if (rect.top < 0) {
                window.scrollTo({
                    top: window.scrollY + rect.top - 20,
                    behavior: 'smooth'
                });
            }
        },

        collectStepData(stepIndex) {
            const step = this.steps[stepIndex];
            if (!step) return;

            const inputs = step.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (input.type === 'file') return;
                if (input.type === 'radio' && !input.checked) return;
                if (input.type === 'checkbox' && !input.checked) return;

                if (input.type === 'checkbox' && input.name.endsWith('[]')) {
                    const key = input.name.replace('[]', '');
                    if (!this.formData[key]) this.formData[key] = [];
                    if (!this.formData[key].includes(input.value)) {
                        this.formData[key].push(input.value);
                    }
                } else {
                    this.formData[input.name] = input.value;
                }
            });
        },

        async submitForm() {
            if (!this.validateContact()) return;

            // Collect all step data
            for (let i = 0; i <= this.currentStep; i++) {
                this.collectStepData(i);
            }

            const submitBtn = document.getElementById('cliff-submit');
            const loading = document.getElementById('cliff-loading');

            submitBtn.disabled = true;
            submitBtn.style.display = 'none';
            loading.style.display = 'block';

            try {
                const formData = new FormData();
                formData.append('action', 'cliff_submit_form');
                formData.append('nonce', cliffForm.nonce);

                // Add all collected data
                Object.keys(this.formData).forEach(key => {
                    const value = this.formData[key];
                    if (Array.isArray(value)) {
                        formData.append(key, value.join(', '));
                    } else {
                        formData.append(key, value);
                    }
                });

                // Add file uploads
                const alaprajz = document.getElementById('cliff-alaprajz');
                if (alaprajz && alaprajz.files.length > 0) {
                    formData.append('alaprajz', alaprajz.files[0]);
                }

                const foto = document.getElementById('cliff-foto');
                if (foto && foto.files.length > 0) {
                    formData.append('foto', foto.files[0]);
                }

                const response = await fetch(cliffForm.ajaxUrl, {
                    method: 'POST',
                    body: formData,
                });

                const result = await response.json();

                if (result.success) {
                    this.goToStep(this.steps.length - 1); // Thank you step
                } else {
                    alert(result.data?.message || 'Hiba történt a küldés során. Kérjük próbálja újra!');
                    submitBtn.disabled = false;
                    submitBtn.style.display = '';
                    loading.style.display = 'none';
                }
            } catch (err) {
                console.error('Cliff form error:', err);
                alert('Hiba történt a küldés során. Kérjük próbálja újra!');
                submitBtn.disabled = false;
                submitBtn.style.display = '';
                loading.style.display = 'none';
            }
        },
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => wizard.init());
    } else {
        wizard.init();
    }
})();
