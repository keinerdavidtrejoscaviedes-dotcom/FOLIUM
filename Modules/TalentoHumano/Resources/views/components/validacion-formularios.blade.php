{{-- Validación uniforme de los formularios de Talento Humano (letras, números, fechas, espacios). --}}
@verbatim
<script>
/**
 * Validación uniforme de formularios para todo el sistema.
 *
 * Cada campo se clasifica por su atributo data-validar o, si no lo tiene,
 * por su type / name / id. Tipos disponibles:
 *   letras    → nombres de personas: solo letras, espacios, tildes, ñ, ' y -
 *   numeros   → documentos, cantidades enteras escritas en texto: solo dígitos
 *   telefono  → dígitos (y + al inicio), máx. 15
 *   decimal   → coordenadas y valores con decimales: dígitos, un punto y signo -
 *   codigo    → códigos tipo FT-001: letras, dígitos, - _ . /
 *   fecha     → fecha escrita como DD/MM/AAAA: solo dígitos, las barras se ponen solas
 *   libre     → no se filtra
 * Además, para todos los campos:
 *   type=number → no deja escribir letras, "e", "+", y "-" / "." solo cuando aplica
 *   type=date   → año de 4 dígitos (rango 1900-9999)
 *   type=email  → exige dominio con punto (usuario@dominio.com)
 *   texto obligatorio → no se acepta un valor de solo espacios
 */
(function () {
    'use strict';

    if (window.__validacionFormularios) return;
    window.__validacionFormularios = true;

    var LETRA = 'A-Za-zÁÉÍÓÚÜÑáéíóúüñ';

    var REGLAS = {
        letras: {
            limpiar: function (v) {
                return v.replace(new RegExp('[^' + LETRA + "\\s'.-]", 'g'), '').replace(/\s{2,}/g, ' ').replace(/^\s+/, '');
            },
            mensaje: 'Solo se permiten letras.',
        },
        numeros: {
            limpiar: function (v) { return v.replace(/\D/g, ''); },
            mensaje: 'Solo se permiten números.',
            inputmode: 'numeric',
        },
        telefono: {
            limpiar: function (v) {
                var mas = v.trim().charAt(0) === '+';
                return (mas ? '+' : '') + v.replace(/\D/g, '').slice(0, 15);
            },
            mensaje: 'Solo se permiten números.',
            inputmode: 'tel',
            maxlength: 16,
        },
        decimal: {
            limpiar: function (v) {
                var neg = v.trim().charAt(0) === '-';
                var limpio = v.replace(/[^0-9.]/g, '');
                var p = limpio.indexOf('.');
                if (p !== -1) limpio = limpio.slice(0, p + 1) + limpio.slice(p + 1).replace(/\./g, '');
                return (neg ? '-' : '') + limpio;
            },
            mensaje: 'Solo se permiten números (use punto para decimales).',
            inputmode: 'decimal',
        },
        sin_numeros: {
            // Nombres de cargos y similares: se permiten letras y signos ( ) / + , pero no números.
            limpiar: function (v) { return v.replace(/\d/g, ''); },
            mensaje: 'No se permiten números en este campo.',
        },
        codigo: {
            limpiar: function (v) { return v.replace(/[^A-Za-z0-9ÑñÁÉÍÓÚáéíóú._\/-]/g, ''); },
            mensaje: 'Solo letras, números y los signos - _ . /',
        },
        fecha: {
            limpiar: function (v) {
                var d = v.replace(/\D/g, '').slice(0, 8);
                if (d.length > 4) return d.slice(0, 2) + '/' + d.slice(2, 4) + '/' + d.slice(4);
                if (d.length > 2) return d.slice(0, 2) + '/' + d.slice(2);
                return d;
            },
            mensaje: 'La fecha solo admite números (DD/MM/AAAA).',
            inputmode: 'numeric',
            maxlength: 10,
            placeholder: 'DD/MM/AAAA',
        },
    };

    // Clasificación automática por name / id (se usa solo si no hay data-validar).
    var POR_NOMBRE = [
        [/^(first_name|first_last_name|second_last_name|full_name|nombres?_?completos?|nombres|apellidos?|responsable|nombre_persona|apellido|jefe_inmediato_texto)$/i, 'letras'],
        [/(^|_)(document_number|documento|numero_documento|cedula|identificacion)$/i, 'numeros'],
        [/(^|_)(telefono|celular|phone|movil)$/i, 'telefono'],
        [/^(codigo|code)$/i, 'codigo'],
    ];

    var TEXTUALES = ['text', 'search', 'tel', ''];

    function claveCampo(el) {
        var n = el.getAttribute('name') || el.id || '';
        // items[3][cantidad] → cantidad
        var m = n.match(/\[([^\]\[]+)\]$/);
        return m ? m[1] : n;
    }

    function tipoDe(el) {
        if (!el || el.tagName !== 'INPUT') return null;
        if (el.hasAttribute('data-validar')) {
            var t = el.getAttribute('data-validar');
            return REGLAS[t] ? t : null;
        }
        var type = (el.getAttribute('type') || 'text').toLowerCase();
        if (type === 'tel') return 'telefono';
        if (TEXTUALES.indexOf(type) === -1) return null;
        if ((el.getAttribute('placeholder') || '').toUpperCase() === 'DD/MM/AAAA') return 'fecha';
        var clave = claveCampo(el);
        for (var i = 0; i < POR_NOMBRE.length; i++) {
            if (POR_NOMBRE[i][0].test(clave)) return POR_NOMBRE[i][1];
        }
        return null;
    }

    // ---------- Mensaje breve bajo el campo ----------
    function avisar(el, texto) {
        var id = el.__fvAviso;
        var aviso = id && document.getElementById(id);
        if (!aviso) {
            aviso = document.createElement('small');
            aviso.id = 'fv-aviso-' + Math.random().toString(36).slice(2);
            aviso.className = 'fv-aviso';
            aviso.setAttribute('role', 'status');
            aviso.style.cssText = 'display:block;margin-top:4px;font-size:12px;font-weight:600;color:#dc2626;line-height:1.3;';
            el.__fvAviso = aviso.id;
            var ancla = el;
            // Si el input comparte fila con botones/iconos (flex en fila, grid, input-group o
            // contenedor relativo con icono), el aviso va debajo de ese contenedor para no deformarlo.
            while (ancla.parentElement && ancla.parentElement.tagName !== 'FORM' && ancla.parentElement !== document.body) {
                var padre = ancla.parentElement;
                var cs = window.getComputedStyle(padre);
                var enFila = (/flex/.test(cs.display) && !/^column/.test(cs.flexDirection)) || /grid/.test(cs.display);
                var conIcono = /(^|\s)relative(\s|$)/.test(padre.className || '') && padre.children.length <= 3;
                if ((enFila || conIcono || /input-group/.test(padre.className || '')) && padre.querySelectorAll('input, textarea, select').length <= 2) {
                    ancla = padre;
                } else {
                    break;
                }
            }
            ancla.insertAdjacentElement('afterend', aviso);
        }
        aviso.textContent = texto;
        clearTimeout(el.__fvTimer);
        el.__fvTimer = setTimeout(function () { if (aviso.parentNode) aviso.parentNode.removeChild(aviso); el.__fvAviso = null; }, 3000);
    }

    // ---------- Preparar atributos ----------
    function preparar(el) {
        if (el.__fvListo) return;
        el.__fvListo = true;
        var type = (el.getAttribute('type') || 'text').toLowerCase();

        if (type === 'date') {
            if (!el.hasAttribute('min')) el.setAttribute('min', '1900-01-01');
            if (!el.hasAttribute('max')) el.setAttribute('max', '9999-12-31');
            return;
        }
        if (type === 'number') {
            if (!el.hasAttribute('inputmode')) el.setAttribute('inputmode', permiteDecimales(el) ? 'decimal' : 'numeric');
            return;
        }
        var t = tipoDe(el);
        if (!t) return;
        var r = REGLAS[t];
        if (r.inputmode && !el.hasAttribute('inputmode')) el.setAttribute('inputmode', r.inputmode);
        if (r.maxlength && !el.hasAttribute('maxlength')) el.setAttribute('maxlength', r.maxlength);
        if (r.placeholder && !el.getAttribute('placeholder')) el.setAttribute('placeholder', r.placeholder);
        if (t === 'letras' && !el.hasAttribute('maxlength')) el.setAttribute('maxlength', '100');
        if (t === 'numeros' && !el.hasAttribute('maxlength')) el.setAttribute('maxlength', '15');
        if (t === 'telefono' || t === 'numeros') el.setAttribute('autocomplete', el.getAttribute('autocomplete') || 'off');
    }

    function prepararTodo(raiz) {
        var campos = (raiz || document).querySelectorAll ? (raiz || document).querySelectorAll('input') : [];
        for (var i = 0; i < campos.length; i++) preparar(campos[i]);
    }

    // ---------- Números (type=number) ----------
    function permiteDecimales(el) {
        var step = (el.getAttribute('step') || '').toLowerCase();
        if (step === 'any') return true;
        if (step && Number(step) % 1 !== 0) return true;
        var min = el.getAttribute('min'), max = el.getAttribute('max');
        return (min && Number(min) % 1 !== 0) || (max && Number(max) % 1 !== 0);
    }
    function permiteNegativos(el) {
        var min = el.getAttribute('min');
        return min === null || min === '' || Number(min) < 0;
    }
    function textoNumericoValido(el, txt) {
        var re = '^' + (permiteNegativos(el) ? '-?' : '') + '\\d*' + (permiteDecimales(el) ? '([.,]\\d*)?' : '') + '$';
        return new RegExp(re).test(txt);
    }

    document.addEventListener('keydown', function (e) {
        var el = e.target;
        if (!el || el.tagName !== 'INPUT' || (el.type || '').toLowerCase() !== 'number') return;
        if (!e.key || e.ctrlKey || e.metaKey || e.altKey || e.key.length !== 1) return;
        var ok = /\d/.test(e.key)
            || (e.key === '-' && permiteNegativos(el))
            || ((e.key === '.' || e.key === ',') && permiteDecimales(el));
        if (!ok) {
            e.preventDefault();
            avisar(el, permiteDecimales(el) ? 'Solo se permiten números.' : 'Solo se permiten números enteros.');
        }
    }, true);

    // Cubre teclados móviles, autocompletado e inserciones que no generan keydown.
    document.addEventListener('beforeinput', function (e) {
        var el = e.target;
        if (!el || el.tagName !== 'INPUT' || (el.type || '').toLowerCase() !== 'number') return;
        if (!e.data || !/^insert/.test(e.inputType || '')) return;
        var permitidos = '0-9' + (permiteNegativos(el) ? '\\-' : '') + (permiteDecimales(el) ? '.,' : '');
        if (new RegExp('[^' + permitidos + ']').test(e.data)) {
            e.preventDefault();
            avisar(el, permiteDecimales(el) ? 'Solo se permiten números.' : 'Solo se permiten números enteros.');
        }
    }, true);

    document.addEventListener('paste', function (e) {
        var el = e.target;
        if (!el || el.tagName !== 'INPUT' || (el.type || '').toLowerCase() !== 'number') return;
        var txt = ((e.clipboardData || window.clipboardData).getData('text') || '').trim();
        if (!textoNumericoValido(el, txt)) {
            e.preventDefault();
            avisar(el, 'Solo se permiten números.');
        }
    }, true);

    // ---------- Filtro en vivo de campos de texto ----------
    document.addEventListener('input', function (e) {
        var el = e.target;
        if (!el || el.tagName !== 'INPUT') return;
        preparar(el);
        var t = tipoDe(el);
        if (!t) return;
        var antes = el.value;
        var despues = REGLAS[t].limpiar(antes);
        if (despues !== antes) {
            var pos = el.selectionStart;
            el.value = despues;
            if (t !== 'fecha' && pos !== null) {
                var nueva = Math.max(0, pos - (antes.length - despues.length));
                try { el.setSelectionRange(nueva, nueva); } catch (x) { /* ignorar */ }
            }
            // En fechas, que las barras se agreguen solas no es un error.
            var soloFormato = t === 'fecha' && antes.replace(/\D/g, '') === despues.replace(/\D/g, '') && antes.replace(/\D/g, '').length <= 8;
            if (!soloFormato) avisar(el, REGLAS[t].mensaje);
        }
        el.setCustomValidity('');
    }, true);

    // ---------- Validación al salir del campo ----------
    function validarCampo(el) {
        if (!el || (el.tagName !== 'INPUT' && el.tagName !== 'TEXTAREA') || el.disabled) return true;
        var type = (el.getAttribute('type') || 'text').toLowerCase();
        var v = el.value;
        var msg = '';

        if (el.tagName === 'TEXTAREA' || TEXTUALES.indexOf(type) !== -1 || type === 'email') {
            if (el.required && v.length && !v.trim().length) msg = 'Este campo no puede quedar solo con espacios.';
        }

        // Fecha incompleta o con datos que no son fecha, o número con letras: el navegador
        // deja el valor vacío y marca badInput. Se trata como error para no enviar.
        if (!msg && el.validity && el.validity.badInput) {
            msg = type === 'number' ? 'Solo se permiten números en este campo.'
                : (type === 'date' ? 'La fecha está incompleta o no es válida. Elíjala en el calendario.' : 'El valor no es válido.');
        }

        var t = tipoDe(el);
        if (!msg && t && v.length) {
            var normal = t === 'letras' ? v.trim().replace(/\s+/g, ' ') : v;
            var limpio = REGLAS[t].limpiar(normal);
            if (limpio !== normal) {
                // Tiene caracteres no permitidos (por ejemplo, letras en un campo de números): no se envía.
                msg = REGLAS[t].mensaje;
            } else if (t === 'letras') {
                el.value = v = normal;
            }
        }
        if (!msg && t && v.length) {
            if (t === 'letras') {
                el.value = v = v.trim();
                if (v && !new RegExp('[' + LETRA + ']').test(v)) msg = 'Escriba un nombre válido.';
            } else if (t === 'sin_numeros') {
                if (v.trim() && !new RegExp('[' + LETRA + ']').test(v)) msg = 'Debe contener letras.';
            } else if (t === 'numeros') {
                if (v.length < 5) msg = 'El número debe tener al menos 5 dígitos.';
            } else if (t === 'telefono') {
                var dig = v.replace(/\D/g, '').length;
                if (dig < 7) msg = 'El teléfono debe tener entre 7 y 15 dígitos.';
            } else if (t === 'decimal') {
                if (!/^-?\d+(\.\d+)?$/.test(v)) msg = 'Escriba un número válido (por ejemplo 2.6857).';
            } else if (t === 'fecha') {
                var m = v.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
                var ok = false;
                if (m) {
                    var d = +m[1], mo = +m[2], a = +m[3];
                    var f = new Date(a, mo - 1, d);
                    ok = a >= 1900 && f.getFullYear() === a && f.getMonth() === mo - 1 && f.getDate() === d;
                }
                if (!ok) msg = 'Escriba una fecha válida con el formato DD/MM/AAAA.';
            }
        }

        if (!msg && type === 'date' && v) {
            var anio = parseInt(v.split('-')[0], 10);
            if (!(anio >= 1900 && anio <= 9999)) msg = 'Escriba una fecha válida (año de 4 dígitos).';
            var fMin = el.getAttribute('min'), fMax = el.getAttribute('max');
            var hoy = new Date(); hoy = hoy.getFullYear() + '-' + ('0' + (hoy.getMonth() + 1)).slice(-2) + '-' + ('0' + hoy.getDate()).slice(-2);
            var fmt = function (iso) { var p = iso.split('-'); return p[2] + '/' + p[1] + '/' + p[0]; };
            if (!msg && fMin && fMin !== '1900-01-01' && v < fMin) {
                msg = fMin === hoy ? 'La fecha no puede ser anterior a hoy.' : 'La fecha no puede ser anterior al ' + fmt(fMin) + '.';
            } else if (!msg && fMax && fMax !== '9999-12-31' && v > fMax) {
                msg = fMax === hoy ? 'La fecha no puede ser posterior a hoy.' : 'La fecha no puede ser posterior al ' + fmt(fMax) + '.';
            }
        }
        // Rango de fechas (data-desde="#id"): la fecha final no puede ser anterior a la inicial.
        if (!msg && type === 'date' && v && el.hasAttribute('data-desde')) {
            var campoDesde = document.querySelector(el.getAttribute('data-desde'));
            if (campoDesde && campoDesde.value && v < campoDesde.value) {
                msg = 'La fecha final no puede ser anterior a la fecha inicial.';
            }
        }
        // Hora ligada a una fecha (data-fecha-de="#id"): si la fecha es hoy, la hora no puede haber pasado.
        if (!msg && type === 'time' && v && el.hasAttribute('data-fecha-de')) {
            var campoFecha = document.querySelector(el.getAttribute('data-fecha-de'));
            var ahora = new Date();
            var hoyIso = ahora.getFullYear() + '-' + ('0' + (ahora.getMonth() + 1)).slice(-2) + '-' + ('0' + ahora.getDate()).slice(-2);
            var horaActual = ('0' + ahora.getHours()).slice(-2) + ':' + ('0' + ahora.getMinutes()).slice(-2);
            if (campoFecha && campoFecha.value === hoyIso && v.slice(0, 5) <= horaActual) {
                msg = 'La hora ya pasó. Elija una hora posterior a las ' + horaActual + '.';
            }
        }
        if (!msg && type === 'email' && v.trim()) {
            el.value = v = v.trim();
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) msg = 'Escriba un correo válido, por ejemplo usuario@dominio.com.';
        }
        if (!msg && type === 'number' && v !== '') {
            var num = Number(v);
            var min = el.getAttribute('min'), max = el.getAttribute('max');
            if (isNaN(num)) msg = 'Escriba un número válido.';
            else if (min !== null && min !== '' && num < Number(min)) msg = 'El valor mínimo es ' + min + '.';
            else if (max !== null && max !== '' && num > Number(max)) msg = 'El valor máximo es ' + max + '.';
        }

        el.setCustomValidity(msg);
        if (msg) avisar(el, msg);
        return !msg;
    }

    document.addEventListener('blur', function (e) { validarCampo(e.target); }, true);

    document.addEventListener('submit', function (e) {
        var form = e.target;
        // También en formularios con novalidate: ahí el servidor muestra "obligatorio",
        // pero letras en números o fechas inválidas se frenan antes de enviar.
        if (!form || form.tagName !== 'FORM' || form.hasAttribute('data-sin-validar')) return;
        var campos = form.querySelectorAll('input, textarea');
        var primero = null;
        for (var i = 0; i < campos.length; i++) {
            if (!validarCampo(campos[i]) && !primero) primero = campos[i];
        }
        if (primero) {
            e.preventDefault();
            e.stopImmediatePropagation();
            try { primero.scrollIntoView({ block: 'center', behavior: 'smooth' }); } catch (x) { /* ignorar */ }
            primero.focus({ preventScroll: true });
            if (primero.reportValidity) primero.reportValidity();
        }
    }, true);

    // Fechas: al hacer clic en cualquier parte del campo se abre el calendario.
    document.addEventListener('click', function (e) {
        var el = e.target;
        if (!el || el.tagName !== 'INPUT' || (el.type || '').toLowerCase() !== 'date' || el.readOnly || el.disabled) return;
        if (typeof el.showPicker === 'function') {
            try { el.showPicker(); } catch (x) { /* el navegador no lo permite: queda el calendario normal */ }
        }
    }, true);

    // Rango de fechas: al cambiar la fecha inicial, el calendario de la final empieza en ese día.
    document.addEventListener('change', function (e) {
        var el = e.target;
        if (!el || el.tagName !== 'INPUT' || (el.type || '').toLowerCase() !== 'date' || !el.id) return;
        var finales = document.querySelectorAll('input[type="date"][data-desde="#' + el.id + '"]');
        for (var i = 0; i < finales.length; i++) {
            if (el.value) finales[i].setAttribute('min', el.value); else finales[i].removeAttribute('min');
            if (finales[i].value) validarCampo(finales[i]);
        }
    }, true);

    // Fechas: no se escriben letras (el calendario solo admite números).
    document.addEventListener('keydown', function (e) {
        var el = e.target;
        if (!el || el.tagName !== 'INPUT' || (el.type || '').toLowerCase() !== 'date') return;
        if (!e.key || e.ctrlKey || e.metaKey || e.altKey || e.key.length !== 1) return;
        if (!/[0-9]/.test(e.key)) {
            e.preventDefault();
            avisar(el, 'La fecha solo admite números. Elíjala en el calendario.');
        }
    }, true);

    // Exponer para formularios que se guardan por JavaScript (fetch / modales).
    window.validarFormulario = function (contenedor) {
        var raiz = typeof contenedor === 'string' ? document.querySelector(contenedor) : contenedor;
        if (!raiz) return true;
        var campos = raiz.querySelectorAll('input, textarea');
        var primero = null;
        for (var i = 0; i < campos.length; i++) {
            if (!validarCampo(campos[i]) && !primero) primero = campos[i];
        }
        if (primero && primero.reportValidity) primero.reportValidity();
        return !primero;
    };

    function iniciar() {
        prepararTodo(document);
        if (window.MutationObserver) {
            new MutationObserver(function (muts) {
                for (var i = 0; i < muts.length; i++) {
                    var nodos = muts[i].addedNodes;
                    for (var j = 0; j < nodos.length; j++) {
                        var n = nodos[j];
                        if (n.nodeType !== 1) continue;
                        if (n.tagName === 'INPUT') preparar(n); else prepararTodo(n);
                    }
                }
            }).observe(document.documentElement, { childList: true, subtree: true });
        }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar); else iniciar();
})();
</script>
@endverbatim
