/*
 * FOLIUM · mockups: piezas compartidas de todas las pantallas.
 * - Encabezado del módulo (igual al layout master de TalentoHumano).
 * - Menú lateral de cada rol (mismo diseño del layout lider del submódulo Instructor Líder de TH).
 * - Cuadro de confirmación, ventanas modales, pestañas y avisos.
 * - Etiqueta de mockup con el selector de estados de cada pantalla.
 *
 * Uso en cada pantalla:
 *   <body data-layout="lider" data-rol="admin" data-activo="catalogo" data-mockup="ADM-04|Catálogo de cargos|HU-003 a HU-006"
 *         data-estados="normal:Normal,vacio:Sin resultados">
 *     <div id="contenido"> ... </div>
 */
(() => {
    const RAIZ = document.currentScript.src.replace(/assets\/folium\.js.*$/, '');

    // Roles de ejemplo (los de cuentas_prueba_folium.txt): usuario, tarjeta y menú lateral de cada uno
    const ROLES = {
        admin: {
            usuario: { completo: 'Yina · Administradora', iniciales: 'YI' },
            tarjeta: { antetitulo: 'Submódulo', titulo: 'Instructor Líder de TH', chips: ['Administrador', 'Líder de la estrategia'] },
            inicio: 'admin/03-tablero.html', alertas: 'admin/17-alertas.html', salir: 'admin/01-presentacion.html',
            secciones: {
                tablero: { titulo: 'Tablero de la fase', icono: 'fa-gauge-high', url: 'admin/03-tablero.html' },
                aprobaciones: { titulo: 'Aprobaciones pendientes', icono: 'fa-clipboard-check', url: 'instructor/01-aprobaciones.html', cuenta: 30 },
                supervision: { titulo: 'Supervisión', icono: 'fa-eye', url: 'admin/14-supervision-seleccion.html' },
                asignaciones: { titulo: 'Asignaciones', icono: 'fa-id-card-clip', url: 'admin/11-asignaciones.html' },
                catalogo: { titulo: 'Catálogo de cargos', icono: 'fa-sitemap', url: 'admin/04-catalogo.html' },
                designaciones: { titulo: 'Designaciones', icono: 'fa-user-tag', url: 'admin/10-designaciones.html' },
                plantillas: { titulo: 'Plantillas de acta', icono: 'fa-file-signature', url: 'admin/07-plantillas.html' },
                alertas: { titulo: 'Alertas y servicios', icono: 'fa-bell', url: 'admin/17-alertas.html', cuenta: 2, alerta: true },
                auditoria: { titulo: 'Auditoría', icono: 'fa-clock-rotate-left', url: 'admin/18-auditoria.html' },
            },
            grupos: { '': ['tablero'], Proceso: ['aprobaciones', 'supervision'], 'Gestión': ['asignaciones', 'catalogo', 'designaciones', 'plantillas'], Sistema: ['alertas', 'auditoria'] },
        },
        gestor: {
            usuario: { completo: 'Laura Valentina Cuéllar', iniciales: 'LC' },
            tarjeta: { antetitulo: 'Convocatoria Fase XXXVI', titulo: 'Gestor de Talento Humano', chips: ['Rol: Aprendiz', 'Rol: Gestor de TH'] },
            inicio: 'gestor/01-panel.html', alertas: 'gestor/01-panel.html#alertas', salir: 'admin/01-presentacion.html',
            secciones: {
                panel: { titulo: 'Mi panel', icono: 'fa-house', url: 'gestor/01-panel.html', cuenta: 3, alerta: true },
                fichas: { titulo: 'Fichas de la fase', icono: 'fa-users-rectangle', url: 'gestor/02-fichas.html' },
                activacion: { titulo: 'Activación de cargos', icono: 'fa-toggle-on', url: 'gestor/03-activacion.html' },
                cronograma: { titulo: 'Cronograma', icono: 'fa-calendar-days', url: 'gestor/04-cronograma.html' },
                convocatoria: { titulo: 'Convocatoria', icono: 'fa-bullhorn', url: 'gestor/05-convocatoria.html' },
                postulantes: { titulo: 'Postulantes', icono: 'fa-file-signature', url: 'gestor/06-postulantes.html' },
                prueba: { titulo: 'Prueba psicotécnica', icono: 'fa-brain', url: 'gestor/08-prueba.html' },
                evaluadores: { titulo: 'Evaluadores', icono: 'fa-user-group', url: 'gestor/09-evaluadores.html' },
                entrevistas: { titulo: 'Entrevistas', icono: 'fa-comments', url: 'gestor/10-entrevistas.html' },
                acta: { titulo: 'Acta de seleccionados', icono: 'fa-user-check', url: 'gestor/11-acta.html' },
                empalmes: { titulo: 'Empalmes', icono: 'fa-handshake', url: 'gestor/12-empalmes.html', cuenta: 6 },
                vinculacion: { titulo: 'Vinculación', icono: 'fa-link', url: 'gestor/14-vinculacion.html' },
                catalogo: { titulo: 'Catálogo de cargos', icono: 'fa-sitemap', url: 'gestor/15-catalogo.html' },
            },
            grupos: { '': ['panel'], 'Activar cargos': ['fichas', 'activacion'], 'Selección': ['cronograma', 'convocatoria', 'postulantes', 'prueba', 'evaluadores', 'entrevistas', 'acta'], Empalme: ['empalmes', 'vinculacion'], Consulta: ['catalogo'] },
        },
        // Instructor con la designación Líder de la estrategia (sin el rol Administrador)
        lider: {
            usuario: { completo: 'Yina · Instructora Líder', iniciales: 'YI' },
            tarjeta: { antetitulo: 'Submódulo', titulo: 'Instructor Líder de TH', chips: ['Líder de la estrategia', 'Instructor de TH'] },
            inicio: 'admin/03-tablero.html', alertas: 'instructor/01-aprobaciones.html', salir: 'admin/01-presentacion.html',
            secciones: {
                tablero: { titulo: 'Tablero de la fase', icono: 'fa-gauge-high', url: 'admin/03-tablero.html' },
                aprobaciones: { titulo: 'Aprobaciones pendientes', icono: 'fa-clipboard-check', url: 'instructor/01-aprobaciones.html', cuenta: 30 },
                reubicaciones: { titulo: 'Reubicaciones', icono: 'fa-arrows-rotate', url: 'instructor/04-reubicaciones.html', cuenta: 3 },
                supervision: { titulo: 'Supervisión', icono: 'fa-eye', url: 'admin/14-supervision-seleccion.html' },
                asignaciones: { titulo: 'Asignaciones', icono: 'fa-id-card-clip', url: 'admin/11-asignaciones.html' },
                catalogo: { titulo: 'Catálogo de cargos', icono: 'fa-sitemap', url: 'gestor/15-catalogo.html' },
                entrevistas: { titulo: 'Mis entrevistas', icono: 'fa-comments', url: 'instructor/07-mis-entrevistas.html', cuenta: 2 },
                formato: { titulo: 'Mi formato de entrevista', icono: 'fa-list-check', url: 'instructor/08-formato.html' },
            },
            grupos: { '': ['tablero'], Proceso: ['aprobaciones', 'reubicaciones', 'supervision'], 'Gestión': ['asignaciones', 'catalogo'], Evaluador: ['entrevistas', 'formato'] },
        },
        // Instructor con la designación Instructor de Talento Humano (no entra al submódulo del Líder)
        instructorth: {
            usuario: { completo: 'Paola Andrea Trujillo', iniciales: 'PT' },
            tarjeta: { antetitulo: 'Convocatoria Fase XXXVI', titulo: 'Instructor de Talento Humano', chips: ['Rol: Instructor', 'Instructor de TH', 'Instructor técnico'] },
            inicio: 'instructor/06-panel-th.html', alertas: 'instructor/06-panel-th.html#aprobaciones', salir: 'admin/01-presentacion.html',
            secciones: {
                panel: { titulo: 'Mi panel', icono: 'fa-house', url: 'instructor/06-panel-th.html' },
                aprobaciones: { titulo: 'Aprobaciones pendientes', icono: 'fa-clipboard-check', url: 'instructor/06-panel-th.html#aprobaciones', cuenta: 30 },
                activacion: { titulo: 'Activación de cargos', icono: 'fa-toggle-on', url: 'gestor/03-activacion.html' },
                postulantes: { titulo: 'Análisis de hojas de vida', icono: 'fa-file-signature', url: 'instructor/05-aspirante.html' },
                evaluadores: { titulo: 'Evaluadores', icono: 'fa-user-group', url: 'gestor/09-evaluadores.html' },
                entrevistas: { titulo: 'Mis entrevistas', icono: 'fa-comments', url: 'instructor/07-mis-entrevistas.html' },
                formato: { titulo: 'Mi formato de entrevista', icono: 'fa-list-check', url: 'instructor/08-formato.html' },
            },
            grupos: { '': ['panel'], 'Selección': ['aprobaciones', 'activacion', 'postulantes', 'evaluadores'], Evaluador: ['entrevistas', 'formato'] },
        },
        // Instructor técnico: solo ve las entrevistas que tiene asignadas
        tecnico: {
            usuario: { completo: 'Jorge Iván Cuéllar', iniciales: 'JC' },
            tarjeta: { antetitulo: 'Convocatoria Fase XXXVI', titulo: 'Evaluador técnico', chips: ['Rol: Instructor', 'Técnico · Producción Agrícola'] },
            inicio: 'instructor/07-mis-entrevistas.html', alertas: 'instructor/07-mis-entrevistas.html', salir: 'admin/01-presentacion.html',
            secciones: {
                entrevistas: { titulo: 'Mis entrevistas', icono: 'fa-comments', url: 'instructor/07-mis-entrevistas.html', cuenta: 4 },
                formato: { titulo: 'Mi formato de entrevista', icono: 'fa-list-check', url: 'instructor/08-formato.html' },
                catalogo: { titulo: 'Catálogo de cargos', icono: 'fa-sitemap', url: 'gestor/15-catalogo.html' },
            },
            grupos: { '': ['entrevistas', 'formato'], Consulta: ['catalogo'] },
        },
        // Psicólogo: rol tomado de SICEFA, sin designación
        psicologo: {
            usuario: { completo: 'Adriana Carolina Muñoz', iniciales: 'AM' },
            tarjeta: { antetitulo: 'Convocatoria Fase XXXVI', titulo: 'Evaluador psicológico', chips: ['Rol: Psicólogo (SICEFA)'] },
            inicio: 'psicologo/01-panel.html', alertas: 'psicologo/01-panel.html', salir: 'admin/01-presentacion.html',
            secciones: {
                panel: { titulo: 'Mi panel', icono: 'fa-house', url: 'psicologo/01-panel.html' },
                pruebas: { titulo: 'Pruebas psicotécnicas', icono: 'fa-brain', url: 'psicologo/02-pruebas.html' },
                resultados: { titulo: 'Resultados de la prueba', icono: 'fa-chart-column', url: 'psicologo/04-resultados.html' },
                entrevistas: { titulo: 'Mis entrevistas', icono: 'fa-comments', url: 'psicologo/05-mis-entrevistas.html', cuenta: 1 },
                formato: { titulo: 'Mi formato de entrevista', icono: 'fa-list-check', url: 'psicologo/07-formato.html' },
                comite: { titulo: 'Comité de reubicación', icono: 'fa-arrows-rotate', url: 'psicologo/08-comite.html', cuenta: 3 },
            },
            grupos: { '': ['panel'], 'Prueba psicotécnica': ['pruebas', 'resultados'], 'Entrevista psicológica': ['entrevistas', 'formato'], 'Selección': ['comite'] },
        },
        // Aprendiz de una ficha habilitada: postulante, luego entrante (o saliente al terminar su fase)
        aprendiz: {
            usuario: { completo: 'Santiago Rojas Polanía', iniciales: 'SR' },
            tarjeta: { antetitulo: 'Ficha 2902187 · Producción Agrícola', titulo: 'Aprendiz', chips: ['Rol: Aprendiz', 'Convocatoria Fase XXXVI'] },
            inicio: 'aprendiz/01-panel.html', alertas: 'aprendiz/01-panel.html', salir: 'admin/01-presentacion.html',
            secciones: {
                panel: { titulo: 'Inicio', icono: 'fa-house', url: 'aprendiz/01-panel.html' },
                cronograma: { titulo: 'Cronograma', icono: 'fa-calendar-days', url: 'aprendiz/02-cronograma.html' },
                convocatoria: { titulo: 'Convocatoria', icono: 'fa-bullhorn', url: 'aprendiz/03-convocatoria.html' },
                postulacion: { titulo: 'Mi postulación', icono: 'fa-id-card', url: 'aprendiz/05-mi-postulacion.html' },
                prueba: { titulo: 'Prueba psicotécnica', icono: 'fa-brain', url: 'aprendiz/06-prueba.html' },
                acta: { titulo: 'Acta de seleccionados', icono: 'fa-user-check', url: 'aprendiz/07-acta.html' },
                empalme: { titulo: 'Mi empalme', icono: 'fa-handshake', url: 'aprendiz/08-empalme.html', cuenta: 1, alerta: true },
                satisfaccion: { titulo: 'Satisfacción del empalme', icono: 'fa-face-smile', url: 'aprendiz/09-satisfaccion.html' },
                cargo: { titulo: 'Mi cargo e historial', icono: 'fa-id-card-clip', url: 'aprendiz/10-mi-cargo.html' },
            },
            grupos: { '': ['panel'], 'Selección': ['cronograma', 'convocatoria', 'postulacion', 'prueba', 'acta'], Empalme: ['empalme', 'satisfaccion'], 'Mi cargo': ['cargo'] },
        },
    };

    const url = (ruta) => RAIZ + ruta;
    // El script se carga en <head>: el cuerpo y el rol se toman al cargar la página
    let cuerpo = null;
    let rol = null;
    let usuario = null;

    // ---------- Encabezado (layout master) ----------
    function encabezado() {
        const sesion = cuerpo.dataset.sesion !== 'no';
        const derecha = sesion ? `
            <a href="${url(rol.alertas)}" class="relative grid h-9 w-9 place-items-center rounded-full text-emerald-100 hover:bg-white/10 hover:text-white" title="Alertas" aria-label="Alertas sin leer">
                <i class="fas fa-bell"></i>
                <span class="absolute right-1 top-1 h-2.5 w-2.5 rounded-full bg-amber-400 ring-2 ring-[#064C3D]"></span>
            </a>
            <a href="${url(rol.inicio)}" class="flex items-center gap-2 rounded-full bg-white/15 py-1 pl-1 pr-3 hover:bg-white/25">
                <span class="grid h-7 w-7 place-items-center rounded-full bg-[#34D399] text-xs font-bold text-[#043A2F]">${usuario.iniciales}</span>
                <span class="hidden max-w-40 truncate md:inline">${usuario.completo}</span>
            </a>
            <a href="${url(rol.salir)}" class="rounded-full px-3 py-1.5 text-emerald-100 hover:bg-white/10 hover:text-white" title="Cerrar sesión">
                <i class="fas fa-right-from-bracket"></i><span class="ml-1 hidden sm:inline">Salir</span>
            </a>` : `
            <a href="${url('admin/02-iniciar-sesion.html')}" class="rounded-full bg-[#34D399] px-4 py-1.5 font-semibold text-[#043A2F] hover:bg-emerald-300">
                <i class="fas fa-right-to-bracket mr-1"></i> Iniciar sesión
            </a>`;

        return `
        <header class="border-b border-white/10 bg-[#064C3D] text-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                <a href="${url('admin/01-presentacion.html')}" class="flex items-center gap-3">
                    <i class="fas fa-users-cog text-2xl"></i>
                    <div>
                        <p class="text-lg font-bold leading-tight">Talento Humano</p>
                        <p class="text-xs text-emerald-100">FOLIUM · SENA Empresa</p>
                    </div>
                </a>
                <div class="flex items-center gap-2 text-sm sm:gap-3">
                    <a href="#" class="hidden rounded-full px-3 py-1.5 text-emerald-100 hover:bg-white/10 hover:text-white sm:inline-block">
                        <i class="fas fa-th-large mr-1"></i> Inicio del ERP
                    </a>
                    ${derecha}
                </div>
            </div>
        </header>`;
    }

    // ---------- Menú lateral del rol (mismo diseño del layout lider) ----------
    function menuLateral(activo) {
        const enlace = (actual) => actual
            ? 'bg-[#064C3D] text-white shadow-md shadow-[#064C3D]/20'
            : 'text-slate-600 hover:bg-[#E8F3EF] hover:text-[#064C3D]';
        const item = (clave) => {
            const s = rol.secciones[clave];
            const actual = activo === clave;
            const cuenta = s.cuenta ? `<span class="ml-auto rounded-full px-2 py-0.5 text-[11px] font-bold ${actual ? 'bg-white/20 text-white' : (s.alerta ? 'bg-amber-100 text-amber-800' : 'bg-[#E8F3EF] text-[#064C3D]')}">${s.cuenta}</span>` : '';
            return `<a href="${url(s.url)}" ${actual ? 'aria-current="page"' : ''}
                       class="flex shrink-0 items-center gap-3 rounded-2xl px-4 py-2.5 text-sm font-semibold transition ${enlace(actual)}">
                        <i class="fas ${s.icono} w-4 text-center" aria-hidden="true"></i> ${s.titulo} ${cuenta}
                    </a>`;
        };

        return `
        <aside class="mb-6 lg:mb-0">
            <div class="lg:sticky lg:top-6">
                <div class="rounded-3xl bg-[#064C3D] p-5 text-white">
                    <p class="text-xs font-semibold uppercase tracking-[.18em] text-[#34D399]">${rol.tarjeta.antetitulo}</p>
                    <p class="mt-1 text-lg font-extrabold leading-tight">${rol.tarjeta.titulo}</p>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        ${rol.tarjeta.chips.map((f) => `<span class="rounded-full bg-white/10 px-2.5 py-0.5 text-[11px] font-semibold ring-1 ring-white/20">${f}</span>`).join('')}
                    </div>
                </div>
                <nav aria-label="Secciones" class="sin-scroll -mx-4 mt-3 flex gap-1.5 overflow-x-auto px-4 pb-1 lg:mx-0 lg:mt-4 lg:block lg:space-y-5 lg:overflow-visible lg:px-0">
                    ${Object.entries(rol.grupos).map(([grupo, claves]) => grupo ? `
                        <div class="contents lg:block">
                            <p class="hidden px-4 pb-1.5 text-[11px] font-bold uppercase tracking-[.18em] text-slate-400 lg:block">${grupo}</p>
                            <div class="contents lg:block lg:space-y-1">${claves.map(item).join('')}</div>
                        </div>` : claves.map(item).join('')).join('')}
                </nav>
            </div>
        </aside>`;
    }

    // ---------- Avisos (equivalentes a session('success'), 'warning', ...) ----------
    const TIPOS_AVISO = {
        success: ['bg-[#E8F3EF] text-[#064C3D]', 'fa-circle-check'],
        info: ['bg-sky-50 text-sky-900', 'fa-circle-info'],
        warning: ['bg-amber-50 text-amber-900', 'fa-triangle-exclamation'],
        error: ['bg-red-50 text-red-800', 'fa-circle-xmark'],
    };
    function htmlAviso(tipo, texto) {
        const [color, icono] = TIPOS_AVISO[tipo] || TIPOS_AVISO.success;
        return `<div class="${color} text-sm" role="${tipo === 'warning' || tipo === 'error' ? 'alert' : 'status'}">
                    <div class="mx-auto flex max-w-7xl items-center gap-2 px-4 py-2.5 sm:px-6"><i class="fas ${icono}" aria-hidden="true"></i> ${texto}</div>
                </div>`;
    }
    window.aviso = (tipo, texto) => {
        let caja = document.getElementById('avisos-flotantes');
        if (!caja) {
            caja = document.createElement('div');
            caja.id = 'avisos-flotantes';
            caja.className = 'fixed inset-x-0 top-0 z-[60] shadow-lg';
            cuerpo.appendChild(caja);
        }
        caja.innerHTML = htmlAviso(tipo, texto);
        caja.firstElementChild.classList.add('fade-in');
        clearTimeout(window.__avisoTiempo);
        window.__avisoTiempo = setTimeout(() => { caja.innerHTML = ''; }, 4500);
    };

    // ---------- Cuadro de confirmación (igual al del layout lider) ----------
    function cuadroConfirmacion() {
        return `
        <dialog id="confirmacion" aria-labelledby="confirmacion-titulo" class="m-auto w-[min(92vw,26rem)] rounded-3xl p-0 shadow-2xl">
            <form method="dialog" class="p-6">
                <h2 id="confirmacion-titulo" class="text-lg font-bold text-slate-900">¿Confirmar?</h2>
                <p id="confirmacion-texto" class="mt-2 text-sm text-slate-600"></p>
                <div class="mt-6 flex justify-end gap-2">
                    <button value="cancelar" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancelar</button>
                    <button value="confirmar" id="confirmacion-aceptar" class="rounded-full bg-[#064C3D] px-4 py-2 text-sm font-semibold text-white hover:bg-[#043A2F]">Confirmar</button>
                </div>
            </form>
        </dialog>`;
    }
    function preguntar(titulo, texto, peligro, alConfirmar) {
        const dialogo = document.getElementById('confirmacion');
        dialogo.querySelector('#confirmacion-titulo').textContent = titulo;
        dialogo.querySelector('#confirmacion-texto').textContent = texto;
        dialogo.querySelector('#confirmacion-aceptar').className = 'rounded-full px-4 py-2 text-sm font-semibold text-white '
            + (peligro ? 'bg-red-600 hover:bg-red-700' : 'bg-[#064C3D] hover:bg-[#043A2F]');
        dialogo.onclose = () => dialogo.returnValue === 'confirmar' && alConfirmar();
        dialogo.showModal();
    }
    window.preguntar = preguntar;

    // Acción simulada: aviso y, si se indica, navegación o cambio de estado
    function completarAccion(el) {
        const modal = el.closest('dialog');
        if (modal && modal.id !== 'confirmacion' && modal.open) modal.close();
        if (el.dataset.ok) aviso(el.dataset.okTipo || 'success', el.dataset.ok);
        if (el.dataset.estadoDestino) cambiarEstado(el.dataset.estadoDestino);
        if (el.dataset.ir) setTimeout(() => { window.location = el.dataset.ir; }, el.dataset.ok ? 700 : 0);
    }

    // ---------- Etiqueta del mockup y selector de estados ----------
    let estadoActual = null;
    function cambiarEstado(clave) {
        estadoActual = clave;
        document.querySelectorAll('[data-estado]').forEach((el) => {
            el.hidden = !el.dataset.estado.split(' ').includes(clave);
        });
        document.querySelectorAll('[data-boton-estado]').forEach((b) => {
            const activo = b.dataset.botonEstado === clave;
            b.setAttribute('aria-pressed', activo);
            b.className = 'rounded-full px-2.5 py-1 text-[11px] font-semibold transition ' + (activo ? 'bg-[#34D399] text-[#043A2F]' : 'text-white/80 ring-1 ring-white/20 hover:bg-white/10');
        });
        history.replaceState(null, '', '#estado=' + clave);
        document.dispatchEvent(new CustomEvent('estado', { detail: clave }));
    }
    window.cambiarEstado = cambiarEstado;

    function etiquetaMockup() {
        if (!cuerpo.dataset.mockup) return '';
        const [codigo, nombre, hu] = cuerpo.dataset.mockup.split('|');
        const estados = (cuerpo.dataset.estados || '').split(',').filter(Boolean).map((e) => e.split(':'));
        return `
        <div id="etiqueta-mockup" class="no-imprimir fixed bottom-4 left-4 z-50 max-w-[calc(100vw-2rem)] rounded-2xl bg-slate-900/95 p-3 text-white shadow-2xl ring-1 ring-white/10 backdrop-blur">
            <div class="flex items-center gap-3">
                <a href="${url('index.html')}" class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-[#34D399] text-[#043A2F]" title="Todos los mockups" aria-label="Todos los mockups"><i class="fas fa-table-cells-large"></i></a>
                <div class="min-w-0">
                    <p class="truncate text-xs font-bold">${codigo} · ${nombre}</p>
                    ${hu ? `<p class="truncate text-[11px] text-white/60">${hu}</p>` : ''}
                </div>
                <button type="button" id="plegar-mockup" class="ml-1 grid h-7 w-7 shrink-0 place-items-center rounded-lg text-white/60 hover:bg-white/10 hover:text-white" aria-label="Plegar etiqueta"><i class="fas fa-chevron-down text-xs"></i></button>
            </div>
            ${estados.length > 1 ? `
            <div id="estados-mockup" class="mt-2.5 flex flex-wrap items-center gap-1.5 border-t border-white/10 pt-2.5">
                <span class="mr-1 text-[11px] font-semibold uppercase tracking-wider text-white/50">Estado</span>
                ${estados.map(([clave, texto]) => `<button type="button" data-boton-estado="${clave}">${texto}</button>`).join('')}
            </div>` : ''}
        </div>`;
    }

    // ---------- Montaje ----------
    document.addEventListener('DOMContentLoaded', () => {
        cuerpo = document.body;
        rol = ROLES[cuerpo.dataset.rol || 'admin'];
        usuario = rol.usuario;
        const contenido = document.getElementById('contenido');
        const layout = cuerpo.dataset.layout || 'ninguno';
        cuerpo.classList.add('min-h-screen', 'bg-slate-50', 'text-slate-800', 'antialiased');
        cuerpo.style.fontFamily = "'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif";

        if (layout !== 'ninguno' && contenido) {
            const marco = document.createElement('div');
            const aviso = cuerpo.dataset.aviso ? htmlAviso(...cuerpo.dataset.aviso.split('|')) : '';
            if (layout === 'lider') {
                marco.innerHTML = `${aviso}${encabezado()}
                    <main>
                        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:grid lg:grid-cols-[250px_minmax(0,1fr)] lg:gap-8 lg:py-8">
                            ${menuLateral(cuerpo.dataset.activo)}
                            <section class="min-w-0" id="ranura"></section>
                        </div>
                    </main>`;
            } else {
                marco.innerHTML = `${aviso}${encabezado()}<main id="ranura" class="${layout === 'simple' ? 'mx-auto max-w-6xl px-4 py-8' : ''}"></main>`;
            }
            cuerpo.insertBefore(marco, contenido);
            marco.querySelector('#ranura').appendChild(contenido);
        }

        cuerpo.insertAdjacentHTML('beforeend', cuadroConfirmacion() + etiquetaMockup());

        // Estados de la pantalla
        const estados = (cuerpo.dataset.estados || '').split(',').filter(Boolean).map((e) => e.split(':')[0]);
        const pedido = (location.hash.match(/estado=([\w-]+)/) || [])[1];
        if (estados.length) cambiarEstado(estados.includes(pedido) ? pedido : estados[0]);
        document.querySelectorAll('[data-boton-estado]').forEach((b) => b.addEventListener('click', () => cambiarEstado(b.dataset.botonEstado)));
        document.getElementById('plegar-mockup')?.addEventListener('click', (e) => {
            const estadosMockup = document.getElementById('estados-mockup');
            const plegado = e.currentTarget.dataset.plegado === '1';
            e.currentTarget.dataset.plegado = plegado ? '' : '1';
            e.currentTarget.querySelector('i').className = 'fas text-xs ' + (plegado ? 'fa-chevron-down' : 'fa-chevron-up');
            if (estadosMockup) estadosMockup.hidden = !plegado;
        });

        // Ventanas modales: [data-abrir="#id"] y [data-cerrar]
        document.addEventListener('click', (e) => {
            const abrir = e.target.closest('[data-abrir]');
            if (abrir) {
                e.preventDefault();
                const modal = document.querySelector(abrir.dataset.abrir);
                if (abrir.dataset.titulo && modal.querySelector('[data-titulo-modal]')) modal.querySelector('[data-titulo-modal]').textContent = abrir.dataset.titulo;
                modal.showModal?.() ?? modal.classList.add('abierto');
                return;
            }
            const cerrar = e.target.closest('[data-cerrar]');
            if (cerrar) {
                e.preventDefault();
                const contenedor = cerrar.closest('dialog, [data-cajon]');
                contenedor.close?.() ?? contenedor.classList.remove('abierto');
                return;
            }
            // Botones de acción con confirmación previa: [data-confirmar] fuera de un formulario
            const accion = e.target.closest('button[data-confirmar], a[data-confirmar]');
            if (accion && !accion.closest('form[data-confirmar]')) {
                e.preventDefault();
                preguntar(accion.dataset.tituloConfirmar || '¿Confirmar?', accion.dataset.confirmar, 'peligro' in accion.dataset, () => completarAccion(accion));
                return;
            }
            // Acción directa simulada: [data-accion]
            const directa = e.target.closest('[data-accion]');
            if (directa) {
                e.preventDefault();
                completarAccion(directa);
            }
        });

        // Formularios simulados: validan [data-obligatorio] y muestran el resultado
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form.method === 'dialog' && form.closest('#confirmacion')) return;
            e.preventDefault();
            const faltantes = [...form.querySelectorAll('[data-obligatorio]')].filter((c) => !c.disabled && c.offsetParent !== null && !c.value.trim());
            form.querySelectorAll('[data-obligatorio]').forEach((c) => {
                c.classList.toggle('border-red-400', faltantes.includes(c));
                c.closest('[data-campo]')?.querySelector('[data-error]')?.toggleAttribute('hidden', !faltantes.includes(c));
            });
            const avisoForm = form.querySelector('[data-aviso-obligatorios]') || document.querySelector(form.dataset.avisoObligatorios || '#aviso-obligatorios');
            avisoForm?.toggleAttribute('hidden', faltantes.length === 0);
            if (faltantes.length) {
                faltantes[0].focus();
                return;
            }
            const enviar = () => completarAccion(form);
            form.dataset.confirmar ? preguntar(form.dataset.tituloConfirmar || '¿Confirmar?', form.dataset.confirmar, 'peligro' in form.dataset, enviar) : enviar();
        });

        // Pestañas: [data-tabs] con [data-tab="x"] y paneles [data-panel="x"]
        document.querySelectorAll('[data-tabs]').forEach((lista) => {
            const grupo = lista.dataset.tabs;
            const pestanas = [...lista.querySelectorAll('[data-tab]')];
            const seleccionar = (clave) => {
                pestanas.forEach((p) => {
                    const activa = p.dataset.tab === clave;
                    p.setAttribute('aria-selected', activa);
                    p.classList.toggle('bg-[#064C3D]', activa);
                    p.classList.toggle('text-white', activa);
                    p.classList.toggle('bg-white', !activa);
                    p.classList.toggle('text-slate-600', !activa);
                });
                document.querySelectorAll(`[data-panel][data-grupo="${grupo}"]`).forEach((panel) => {
                    panel.hidden = panel.dataset.panel !== clave;
                    if (!panel.hidden) { panel.classList.remove('fade-in'); void panel.offsetWidth; panel.classList.add('fade-in'); }
                });
            };
            pestanas.forEach((p) => p.addEventListener('click', () => seleccionar(p.dataset.tab)));
            seleccionar((pestanas.find((p) => p.getAttribute('aria-selected') === 'true') || pestanas[0]).dataset.tab);
        });
    });
})();
