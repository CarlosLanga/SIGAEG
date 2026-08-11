$(function () {
    const masks = {
        date: {
            template: "dd.mm.aaaa",
            tokens: ["d", "m", "a"],
            normalize(value) {
                const raw = String(value || "");
                const iso = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                if (iso) {
                    return `${iso[3]}${iso[2]}${iso[1]}`;
                }
                return raw.replace(/\D/g, "");
            },
            isComplete(value) {
                return /^\d{2}\.\d{2}\.\d{4}$/.test(value);
            },
            isValid(value) {
                if (!this.isComplete(value)) return false;
                const [day, month, year] = value.split(".").map(Number);
                const date = new Date(year, month - 1, day);
                const currentYear = new Date().getFullYear();

                return (
                    year >= 1900 &&
                    year <= currentYear &&
                    date.getFullYear() === year &&
                    date.getMonth() === month - 1 &&
                    date.getDate() === day
                );
            },
            canPlace(digit, index, currentValue) {
                const value = currentValue || this.template;
                const currentYear = String(new Date().getFullYear());

                if (index === 0) return /[0-3]/.test(digit);
                if (index === 1) {
                    const first = value[0];
                    if (first === "0") return /[1-9]/.test(digit);
                    if (first === "1" || first === "2") return /\d/.test(digit);
                    if (first === "3") return /[0-1]/.test(digit);
                    return false;
                }

                if (index === 3) return /[0-1]/.test(digit);
                if (index === 4) {
                    const first = value[3];
                    if (first === "0") return /[1-9]/.test(digit);
                    if (first === "1") return /[0-2]/.test(digit);
                    return false;
                }

                if (index === 6) return /[1-2]/.test(digit);
                if (index === 7) {
                    if (value[6] === "1") return digit === "9";
                    if (value[6] === "2") return digit === "0";
                    return false;
                }
                if (index === 8) {
                    if (value.slice(6, 8) === "20") return Number(digit) <= Number(currentYear[2]);
                    return /\d/.test(digit);
                }
                if (index === 9) {
                    if (value.slice(6, 9) === currentYear.slice(0, 3)) {
                        return Number(digit) <= Number(currentYear[3]);
                    }
                    return /\d/.test(digit);
                }

                return false;
            }
        },
        "mz-contact": {
            template: "+258(XX)XXX-XXXX",
            tokens: ["X"],
            normalize(value) {
                let digits = String(value || "").replace(/\D/g, "");
                if (digits.startsWith("258")) {
                    digits = digits.slice(3);
                }
                return digits.slice(0, 9);
            },
            isComplete(value) {
                return /^\+258\(8\d\)\d{3}-\d{4}$/.test(value);
            },
            isValid(value) {
                return value === this.template || this.isComplete(value);
            },
            canPlace(digit, index) {
                if (index === 5) return digit === "8";
                return /\d/.test(digit);
            }
        },
        "formando-code": {
            template: "100XXXXXX",
            tokens: ["X"],
            normalize(value) {
                let digits = String(value || "").replace(/\D/g, "");
                if (digits.startsWith("100")) {
                    digits = digits.slice(3);
                }
                return digits.slice(0, 6);
            },
            isComplete(value) {
                return /^100\d{6}$/.test(value);
            },
            isValid(value) {
                return value === this.template || this.isComplete(value);
            },
            canPlace(digit) {
                return /\d/.test(digit);
            }
        }
    };

    function isEditable(spec, index) {
        return new Set(spec.tokens || []).has(spec.template[index]);
    }

    function nextEditable(spec, index) {
        const template = spec.template;
        for (let i = Math.max(0, index); i < template.length; i++) {
            if (isEditable(spec, i)) return i;
        }
        return -1;
    }

    function previousEditable(spec, index) {
        const template = spec.template;
        for (let i = Math.min(template.length - 1, index); i >= 0; i--) {
            if (isEditable(spec, i)) return i;
        }
        return -1;
    }

    function firstEmptyEditable(value, spec) {
        const template = spec.template;
        for (let i = 0; i < template.length; i++) {
            if (isEditable(spec, i) && value[i] === template[i]) return i;
        }
        return nextEditable(spec, 0);
    }

    function setCaret(input, index) {
        requestAnimationFrame(() => input.setSelectionRange(index, index));
    }

    function ensureTemplate(input, spec) {
        if (!input.value) {
            input.value = spec.template;
            return;
        }

        const digits = spec.normalize(input.value);
        input.value = fillTemplate(spec, digits);
    }

    function fillTemplate(spec, digits) {
        const template = spec.template;
        let value = template;
        let cursor = nextEditable(spec, 0);

        for (const digit of digits) {
            if (cursor < 0) break;
            if (!spec.canPlace(digit, cursor, value)) continue;
            value = value.substring(0, cursor) + digit + value.substring(cursor + 1);
            cursor = nextEditable(spec, cursor + 1);
        }

        return value;
    }

    function placeDigit(input, spec, digit) {
        let value = input.value || spec.template;
        let index = nextEditable(spec, input.selectionStart || 0);

        if (index < 0) return;
        if (!spec.canPlace(digit, index, value)) return;

        value = value.substring(0, index) + digit + value.substring(index + 1);
        input.value = value;

        const next = nextEditable(spec, index + 1);
        setCaret(input, next >= 0 ? next : spec.template.length);
    }

    function clearAt(input, spec, index) {
        if (index < 0) return;
        const token = spec.template[index];
        input.value = input.value.substring(0, index) + token + input.value.substring(index + 1);
        setCaret(input, index);
    }

    function showMaskMessage(input) {
        const message = input.dataset.maskMessage || "Preencha o campo no formato indicado.";
        if (typeof showNotification === "function") {
            showNotification(message, false);
        } else {
            input.setCustomValidity(message);
            input.reportValidity();
            input.setCustomValidity("");
        }
    }

    function refreshMasks(scope) {
        $(scope || document).find("[data-input-mask]").each(function () {
            const spec = masks[this.dataset.inputMask];
            if (spec) ensureTemplate(this, spec);
        });
    }

    window.IICAEGMasks = {
        refresh: refreshMasks
    };

    $("[data-input-mask]").each(function () {
        const input = this;
        const spec = masks[input.dataset.inputMask];
        if (!spec) return;

        ensureTemplate(input, spec);

        $(input)
            .on("focus click", function () {
                ensureTemplate(input, spec);
                setCaret(input, firstEmptyEditable(input.value, spec));
            })
            .on("beforeinput", function (event) {
                const original = event.originalEvent || event;
                const type = original.inputType || "";
                const data = original.data || "";

                if (type === "insertText" && /^\d+$/.test(data)) {
                    event.preventDefault();
                    for (const digit of data) {
                        placeDigit(input, spec, digit);
                    }
                    return;
                }

                if (type === "deleteContentBackward") {
                    event.preventDefault();
                    const index = previousEditable(spec, (input.selectionStart || 0) - 1);
                    clearAt(input, spec, index);
                    return;
                }

                if (type === "deleteContentForward") {
                    event.preventDefault();
                    const index = nextEditable(spec, input.selectionStart || 0);
                    clearAt(input, spec, index);
                    return;
                }

                if (type !== "insertFromPaste") {
                    event.preventDefault();
                }
            })
            .on("keydown", function (event) {
                const key = event.key;

                if (event.ctrlKey || event.metaKey || event.altKey) return;
                if (["Tab", "ArrowLeft", "ArrowRight", "Home", "End"].includes(key)) return;

                event.preventDefault();

                if (/^\d$/.test(key)) {
                    placeDigit(input, spec, key);
                    return;
                }

                if (key === "Backspace") {
                    const index = previousEditable(spec, (input.selectionStart || 0) - 1);
                    clearAt(input, spec, index);
                    return;
                }

                if (key === "Delete") {
                    const index = nextEditable(spec, input.selectionStart || 0);
                    clearAt(input, spec, index);
                }
            })
            .on("paste", function (event) {
                event.preventDefault();
                const text = (event.originalEvent || event).clipboardData.getData("text");
                const digits = spec.normalize(text);

                for (const digit of digits) {
                    placeDigit(input, spec, digit);
                }
            });
    });

    $("form").on("submit", function (event) {
        const $maskedFields = $(this).find("[data-input-mask]");
        let invalidInput = null;

        $maskedFields.each(function () {
            const input = this;
            const spec = masks[input.dataset.inputMask];
            if (!spec) return;

            if (input.value) {
                ensureTemplate(input, spec);
            }

            if (input.required && input.value === spec.template) {
                invalidInput = input;
                return false;
            }

            if (input.value !== spec.template && !spec.isValid(input.value)) {
                invalidInput = input;
                return false;
            }
        });

        if (invalidInput) {
            event.preventDefault();
            event.stopImmediatePropagation();
            showMaskMessage(invalidInput);
            invalidInput.focus();
            return;
        }

        $maskedFields.each(function () {
            const spec = masks[this.dataset.inputMask];
            if (spec && this.value === spec.template) {
                this.value = "";
            }
        });
    });

    $("form").on("reset form:reset", function () {
        $(this).find("[data-input-mask]").each(function () {
            const spec = masks[this.dataset.inputMask];
            if (spec) this.value = spec.template;
        });
    });
});
