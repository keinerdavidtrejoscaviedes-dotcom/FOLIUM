<?php

namespace Modules\SICA\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\SICA\Entities\Bloque;
use Modules\SICA\Entities\App;

class BloqueAppSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Crear Bloque: Procesos Estratégicos
        $estrategico = Bloque::updateOrCreate(['slug' => 'estrategicos'], [
            'name' => 'Procesos Estratégicos',
            'description' => 'Direccionamiento institucional, planeación y evaluación de indicadores globales.',
            'icon' => 'fas fa-chess-king',
            'color' => '#39A900',
            'order_index' => 1
        ]);

        // 2. Crear Bloque: Procesos Misionales
        $misional = Bloque::updateOrCreate(['slug' => 'misionales'], [
            'name' => 'Procesos Misionales',
            'description' => 'Operación central de SENA Empresa: control de stock, comercialización y aprovisionamiento.',
            'icon' => 'fas fa-bullseye',
            'color' => '#00324D',
            'order_index' => 2
        ]);

        // 3. Crear Bloque: Procesos de Apoyo
        $apoyo = Bloque::updateOrCreate(['slug' => 'apoyos'], [
            'name' => 'Procesos de Apoyo',
            'description' => 'Soporte contable, financiero y gestión integral del capital humano.',
            'icon' => 'fas fa-handshake',
            'color' => '#e65100',
            'order_index' => 3
        ]);

        // --- APLICATIVOS / SUBMÓDULOS ---

        // Submódulos Estratégicos
        App::updateOrCreate(['name' => 'Dirección'], [
            'bloque_id' => $estrategico->id,
            'url' => '/direccion',
            'color' => '#1b5e20',
            'icon' => 'fas fa-user-tie',
            'description' => 'Toma de decisiones directivas, formulación de políticas institucionales, gobierno corporativo y liderazgo.',
            'description_english' => 'Executive decision making, institutional policies and leadership.'
        ]);

        App::updateOrCreate(['name' => 'Planeación'], [
            'bloque_id' => $estrategico->id,
            'url' => '/planeacion',
            'color' => '#39A900',
            'icon' => 'fas fa-clipboard-list',
            'description' => 'Planificación estratégica, formulación de metas organizacionales, plan de acción anual y asignación de metas.',
            'description_english' => 'Strategic planning, organizational goal setting and annual action plans.'
        ]);

        App::updateOrCreate(['name' => 'Indicadores'], [
            'bloque_id' => $estrategico->id,
            'url' => '/indicadores',
            'color' => '#20c997',
            'icon' => 'fas fa-chart-line',
            'description' => 'Tablero de control ejecutivo, medición de KPIs de rendimiento empresarial, evaluación de resultados y estadísticas.',
            'description_english' => 'Executive KPI dashboard, enterprise performance measurement and statistics.'
        ]);

        // Submódulos Misionales
        App::updateOrCreate(['name' => 'Inventario'], [
            'bloque_id' => $misional->id,
            'url' => '/inventario',
            'color' => '#00324D',
            'icon' => 'fas fa-boxes',
            'description' => 'Control de stock en bodegas, trazabilidad de insumos agroindustriales, materias primas y almacenes.',
            'description_english' => 'Warehouse stock control, agro-industrial inputs and raw material traceability.'
        ]);

        App::updateOrCreate(['name' => 'Ventas'], [
            'bloque_id' => $misional->id,
            'url' => '/ventas',
            'color' => '#0288d1',
            'icon' => 'fas fa-cash-register',
            'description' => 'Puntos de venta (POS), comercialización de productos del centro de formación y facturación rápida.',
            'description_english' => 'Points of sale (POS), product commercialization and fast billing.'
        ]);

        App::updateOrCreate(['name' => 'Compras'], [
            'bloque_id' => $misional->id,
            'url' => '/compras',
            'color' => '#0097a7',
            'icon' => 'fas fa-shopping-cart',
            'description' => 'Gestión de proveedores, cotizaciones, solicitudes de insumos y órdenes de adquisición.',
            'description_english' => 'Supplier management, quotations, supply requests and purchase orders.'
        ]);

        // Submódulos de Apoyo
        App::updateOrCreate(['name' => 'Contabilidad'], [
            'bloque_id' => $apoyo->id,
            'url' => '/contabilidad',
            'color' => '#f57c00',
            'icon' => 'fas fa-file-invoice-dollar',
            'description' => 'Gestión contable y financiera, registro de comprobantes, presupuestos, asientos y balances generales.',
            'description_english' => 'Financial and accounting management, vouchers, budgets and balances.'
        ]);

        App::updateOrCreate(['name' => 'Talento Humano'], [
            'bloque_id' => $apoyo->id,
            'url' => '/talento-humano',
            'color' => '#ff9800',
            'icon' => 'fas fa-users-cog',
            'description' => 'Administración de aprendices, instructores, asignación de turnos operacionales en SENA Empresa y control de asistencia.',
            'description_english' => 'Management of apprentices, instructors, operational shifts and attendance control.'
        ]);
    }
}
