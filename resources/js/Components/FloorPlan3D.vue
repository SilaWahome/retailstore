<template>
    <div class="flex flex-col lg:flex-row gap-4">
        <!-- 3D Viewport -->
        <div class="flex-1 relative bg-slate-900 rounded-2xl overflow-hidden border border-slate-700 shadow-lg min-h-[520px] h-[64vh]">
            <div ref="canvasHost" class="w-full h-full cursor-grab active:cursor-grabbing"></div>

            <!-- Top-left HUD -->
            <div class="absolute top-3 left-3 flex flex-wrap items-center gap-2 pointer-events-none">
                <div class="bg-slate-950/80 backdrop-blur text-white px-3 py-1.5 rounded-xl text-xs font-bold border border-white/10">
                    {{ area.name }} • {{ totalSqm.toFixed(1) }} m² ({{ areaWidth }}m × {{ areaLength }}m)
                </div>
                <div
                    v-if="editMode"
                    class="bg-amber-500 text-slate-950 px-3 py-1.5 rounded-xl text-xs font-extrabold shadow"
                >
                    DESIGNER MODE: Drag tables or counters on the floor
                </div>
            </div>

            <!-- Top-right Camera & Mode Controls -->
            <div class="absolute top-3 right-3 flex items-center gap-2">
                <button
                    @click="resetCamera"
                    class="px-3 py-1.5 rounded-xl bg-slate-950/80 hover:bg-slate-800 text-white text-xs font-bold border border-white/15 backdrop-blur"
                >
                    Reset View
                </button>
                <button
                    v-if="canEdit"
                    @click="toggleEditMode"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-extrabold shadow transition"
                    :class="editMode ? 'bg-amber-400 text-slate-950' : 'bg-white text-slate-900 hover:bg-slate-100'"
                >
                    {{ editMode ? 'Done Editing' : 'Edit 3D Layout' }}
                </button>
            </div>

            <!-- Bottom-left Controls Hint -->
            <div class="absolute bottom-3 left-3 bg-slate-950/75 text-slate-300 px-3 py-1.5 rounded-xl text-[11px] backdrop-blur border border-white/10 pointer-events-none">
                {{ editMode ? 'Drag object to move (0.5m snap) • Orbit with empty space' : 'Drag to orbit • Scroll to zoom • Tap a table to open order' }}
            </div>
        </div>

        <!-- Space Budget & Inspector Panel (visible in Edit Mode or when toggled) -->
        <aside v-if="editMode" class="w-full lg:w-80 bg-white rounded-2xl border border-slate-200 p-4 shadow-sm space-y-4 h-fit">
            <div>
                <h3 class="font-extrabold text-sm text-slate-900">Floor Dimensions & Space Budget</h3>
                <div class="grid grid-cols-2 gap-2 mt-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500">Width (m)</label>
                        <input v-model.number="areaWidth" @change="rebuildScene" type="number" step="0.5" min="4" max="80" class="w-full px-2.5 py-1.5 rounded-lg border text-sm font-bold" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500">Length (m)</label>
                        <input v-model.number="areaLength" @change="rebuildScene" type="number" step="0.5" min="4" max="80" class="w-full px-2.5 py-1.5 rounded-lg border text-sm font-bold" />
                    </div>
                </div>

                <!-- Space Breakdown -->
                <div class="mt-3 p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-600">Total Floor Space:</span>
                        <span class="font-extrabold text-slate-900">{{ totalSqm.toFixed(1) }} m²</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Counter & Zones:</span>
                        <span class="font-bold text-indigo-700">{{ zonesSqm.toFixed(1) }} m²</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Tables + Seating:</span>
                        <span class="font-bold text-emerald-700">{{ tablesSqm.toFixed(1) }} m²</span>
                    </div>
                    <div class="flex justify-between pt-1.5 border-t border-slate-200">
                        <span class="font-bold text-slate-700">Open Walkways:</span>
                        <span class="font-extrabold" :class="freeSqm < totalSqm * 0.25 ? 'text-rose-600' : 'text-slate-900'">
                            {{ freeSqm.toFixed(1) }} m² ({{ Math.max(0, Math.round((freeSqm / Math.max(1, totalSqm)) * 100)) }}%)
                        </span>
                    </div>
                </div>
            </div>

            <!-- Quick Select + 2D Top-Down Drag Map -->
            <div class="pt-3 border-t border-slate-200 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold text-slate-700">2D Top-Down Map (Drag Here Too)</span>
                    <span class="text-[10px] text-slate-400">Syncs live with 3D</span>
                </div>
                <div
                    ref="map2dRef"
                    class="relative w-full bg-slate-100 border-2 border-slate-300 rounded-xl overflow-hidden select-none touch-none"
                    :style="{ aspectRatio: `${areaWidth} / ${areaLength}` }"
                >
                    <!-- Zones on 2D map -->
                    <div
                        v-for="z in localZones"
                        :key="'2dz-' + z.id"
                        @pointerdown.stop="start2DDrag('zone', z, $event)"
                        class="absolute -translate-x-1/2 -translate-y-1/2 rounded flex items-center justify-center text-[9px] font-extrabold text-white cursor-move shadow-sm border"
                        :class="selectedZoneId === z.id ? 'ring-2 ring-yellow-400 border-yellow-300' : 'border-white/40'"
                        :style="{
                            left: `${(z.pos_x / areaWidth) * 100}%`,
                            top: `${(z.pos_z / areaLength) * 100}%`,
                            width: `${(z.width_m / areaWidth) * 100}%`,
                            height: `${(z.depth_m / areaLength) * 100}%`,
                            backgroundColor: z.zone_type === 'floral' ? '#16a34a' : z.zone_type === 'bar' ? '#4f46e5' : '#1e293b',
                        }"
                    >
                        <span class="truncate px-0.5">{{ z.name }}</span>
                    </div>

                    <!-- Tables on 2D map -->
                    <div
                        v-for="t in localTables"
                        :key="'2dt-' + t.id"
                        @pointerdown.stop="start2DDrag('table', t, $event)"
                        class="absolute -translate-x-1/2 -translate-y-1/2 flex items-center justify-center text-[9px] font-extrabold text-white cursor-move shadow border"
                        :class="[
                            t.shape === 'round' || t.shape === 'high_table' ? 'rounded-full' : 'rounded-md',
                            selectedTableId === t.id ? 'ring-2 ring-yellow-400 border-yellow-300 bg-amber-600' : 'border-white/60 bg-emerald-600'
                        ]"
                        :style="{
                            left: `${(t.pos_x / areaWidth) * 100}%`,
                            top: `${(t.pos_z / areaLength) * 100}%`,
                            width: `${Math.max(8, (t.width_m / areaWidth) * 100)}%`,
                            height: `${Math.max(10, (t.depth_m / areaLength) * 100)}%`,
                        }"
                    >
                        {{ t.table_number }}
                    </div>
                </div>
            </div>

            <!-- Selected Table Inspector -->
            <div v-if="selectedTable" class="pt-3 border-t border-slate-200 space-y-2.5">
                <div class="flex items-center justify-between">
                    <h4 class="font-extrabold text-sm text-slate-900">Table {{ selectedTable.table_number }}</h4>
                    <button @click="rotateSelectedTable" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-bold">
                        Rotate +45° ({{ selectedTable.rotation_deg }}°)
                    </button>
                </div>

                <!-- Directional Arrow Pad -->
                <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-200 flex items-center justify-between gap-2">
                    <div class="grid grid-cols-3 gap-1 w-28">
                        <div></div>
                        <button type="button" @click="nudgeSelected(0, -0.5)" class="h-10 rounded-lg bg-white border border-slate-300 hover:bg-slate-100 active:scale-95 touch-manipulation font-extrabold text-sm shadow-sm">↑</button>
                        <div></div>
                        <button type="button" @click="nudgeSelected(-0.5, 0)" class="h-10 rounded-lg bg-white border border-slate-300 hover:bg-slate-100 active:scale-95 touch-manipulation font-extrabold text-sm shadow-sm">←</button>
                        <button type="button" @click="nudgeSelected(0, 0.5)" class="h-10 rounded-lg bg-white border border-slate-300 hover:bg-slate-100 active:scale-95 touch-manipulation font-extrabold text-sm shadow-sm">↓</button>
                        <button type="button" @click="nudgeSelected(0.5, 0)" class="h-10 rounded-lg bg-white border border-slate-300 hover:bg-slate-100 active:scale-95 touch-manipulation font-extrabold text-sm shadow-sm">→</button>
                    </div>
                    <div class="flex-1 grid grid-cols-2 gap-1.5 text-[11px]">
                        <div>
                            <label class="block font-bold text-slate-500">X (m)</label>
                            <input v-model.number="selectedTable.pos_x" @change="syncSelectedPosition" type="number" step="0.5" min="0.5" :max="areaWidth - 0.5" class="w-full px-2 py-1 rounded border bg-white font-bold" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-500">Z (m)</label>
                            <input v-model.number="selectedTable.pos_z" @change="syncSelectedPosition" type="number" step="0.5" min="0.5" :max="areaLength - 0.5" class="w-full px-2 py-1 rounded border bg-white font-bold" />
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500">Type</label>
                        <select v-model="selectedTable.shape" @change="onTableTypeChange" class="w-full px-2 py-1.5 rounded-lg border text-xs font-semibold">
                            <option value="square">Square</option>
                            <option value="rounded">Rounded</option>
                            <option value="round">Round</option>
                            <option value="high_table">High Table</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500">Width (m)</label>
                        <input v-model.number="selectedTable.width_m" @change="rebuildScene" type="number" step="0.1" min="0.5" max="5" class="w-full px-2 py-1.5 rounded-lg border text-xs font-bold" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500">Depth (m)</label>
                        <input v-model.number="selectedTable.depth_m" @change="rebuildScene" type="number" step="0.1" min="0.5" max="5" class="w-full px-2 py-1.5 rounded-lg border text-xs font-bold" />
                    </div>
                </div>
                <label class="flex items-center gap-2 text-xs font-semibold text-slate-700">
                    <input
                        type="checkbox"
                        :checked="selectedTable.shape === 'high_table' || selectedTable.has_floral"
                        :disabled="selectedTable.shape === 'high_table'"
                        @change="selectedTable.has_floral = $event.target.checked; rebuildScene()"
                    />
                    <span>{{ selectedTable.shape === 'high_table' ? 'High Table (Always Decorated)' : 'Floral Centerpiece' }}</span>
                </label>
                <div class="text-[11px] text-slate-500">
                    Footprint w/ chairs: <strong>{{ tableFootprintSqm(selectedTable).toFixed(1) }} m²</strong> • Pos: ({{ selectedTable.pos_x }}m, {{ selectedTable.pos_z }}m)
                </div>
            </div>

            <!-- Selected Zone Inspector -->
            <div v-if="selectedZone" class="pt-3 border-t border-slate-200 space-y-2">
                <div class="flex items-center justify-between">
                    <h4 class="font-extrabold text-sm text-slate-900">{{ selectedZone.name }}</h4>
                    <button @click="deleteZone(selectedZone)" class="text-xs font-bold text-rose-600 hover:underline">Remove</button>
                </div>
                <div class="bg-slate-50 p-2 rounded-xl border border-slate-200 flex items-center justify-between gap-2">
                    <div class="grid grid-cols-3 gap-1 w-28">
                        <div></div>
                        <button type="button" @click="nudgeSelected(0, -0.5)" class="h-7 rounded bg-white border border-slate-300 hover:bg-slate-100 font-extrabold text-xs">↑</button>
                        <div></div>
                        <button type="button" @click="nudgeSelected(-0.5, 0)" class="h-7 rounded bg-white border border-slate-300 hover:bg-slate-100 font-extrabold text-xs">←</button>
                        <button type="button" @click="nudgeSelected(0, 0.5)" class="h-7 rounded bg-white border border-slate-300 hover:bg-slate-100 font-extrabold text-xs">↓</button>
                        <button type="button" @click="nudgeSelected(0.5, 0)" class="h-7 rounded bg-white border border-slate-300 hover:bg-slate-100 font-extrabold text-xs">→</button>
                    </div>
                    <div class="text-[11px] text-slate-500">
                        Pos: ({{ selectedZone.pos_x }}m, {{ selectedZone.pos_z }}m)
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500">Width (m)</label>
                        <input v-model.number="selectedZone.width_m" @change="rebuildScene" type="number" step="0.2" min="0.5" max="30" class="w-full px-2 py-1.5 rounded-lg border text-xs font-bold" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500">Depth (m)</label>
                        <input v-model.number="selectedZone.depth_m" @change="rebuildScene" type="number" step="0.2" min="0.5" max="30" class="w-full px-2 py-1.5 rounded-lg border text-xs font-bold" />
                    </div>
                </div>
                <div class="text-[11px] text-slate-500">
                    Area: <strong>{{ (selectedZone.width_m * selectedZone.depth_m).toFixed(1) }} m²</strong>
                </div>
            </div>

            <!-- Add Counter / Zone / Floral -->
            <div class="pt-3 border-t border-slate-200 space-y-2">
                <div class="text-xs font-extrabold text-slate-700">Add Counter, Zone or Floral</div>
                <div class="grid grid-cols-2 gap-2">
                    <input v-model="newZone.name" type="text" placeholder="e.g. Floral Planter" class="px-2.5 py-1.5 rounded-lg border text-xs" />
                    <select v-model="newZone.zone_type" class="px-2 py-1.5 rounded-lg border text-xs">
                        <option value="counter">Counter</option>
                        <option value="bar">Bar</option>
                        <option value="kitchen">Kitchen Pass</option>
                        <option value="entrance">Entrance</option>
                        <option value="floral">Floral / Planter</option>
                    </select>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <input v-model.number="newZone.width_m" type="number" step="0.5" min="0.5" placeholder="W (m)" class="px-2 py-1.5 rounded-lg border text-xs" />
                    <input v-model.number="newZone.depth_m" type="number" step="0.5" min="0.5" placeholder="D (m)" class="px-2 py-1.5 rounded-lg border text-xs" />
                    <button @click="addZone" class="rounded-lg bg-slate-900 text-white text-xs font-bold hover:bg-slate-800">+ Add</button>
                </div>
            </div>

            <!-- Save Layout -->
            <button
                @click="saveLayout"
                :disabled="isSaving"
                class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-sm shadow-sm disabled:opacity-50"
            >
                {{ isSaving ? 'Saving 3D Layout...' : 'Save 3D Layout' }}
            </button>
        </aside>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onUnmounted } from 'vue';
import * as THREE from 'three';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';
import axios from 'axios';

const props = defineProps({
    area: { type: Object, required: true },
    canEdit: { type: Boolean, default: false },
});

const emit = defineEmits(['select-table', 'layout-saved']);

const canvasHost = ref(null);
const map2dRef = ref(null);
const editMode = ref(false);
const isSaving = ref(false);

const areaWidth = ref(Number(props.area.width_m || 15));
const areaLength = ref(Number(props.area.length_m || 12.33));
const localTables = ref((props.area.tables || []).map(t => ({
    ...t,
    pos_x: Number(t.pos_x ?? 2),
    pos_z: Number(t.pos_z ?? 2),
    width_m: Number(t.width_m ?? 1.2),
    depth_m: Number(t.depth_m ?? 1.2),
    rotation_deg: Number(t.rotation_deg ?? 0),
    shape: t.shape || 'rect',
})));
const localZones = ref((props.area.zones || []).map(z => ({
    ...z,
    pos_x: Number(z.pos_x ?? 2),
    pos_z: Number(z.pos_z ?? 1),
    width_m: Number(z.width_m ?? 3.5),
    depth_m: Number(z.depth_m ?? 1.2),
})));

const selectedTableId = ref(null);
const selectedZoneId = ref(null);
const selectedTable = computed(() => localTables.value.find(t => t.id === selectedTableId.value) || null);
const selectedZone = computed(() => localZones.value.find(z => z.id === selectedZoneId.value) || null);

const newZone = reactive({ name: 'Main Counter', zone_type: 'counter', width_m: 4.0, depth_m: 1.2 });

const tableFootprintSqm = (t) => (Number(t.width_m) + 0.9) * (Number(t.depth_m) + 0.9);
const totalSqm = computed(() => areaWidth.value * areaLength.value);
const zonesSqm = computed(() => localZones.value.reduce((s, z) => s + Number(z.width_m) * Number(z.depth_m), 0));
const tablesSqm = computed(() => localTables.value.reduce((s, t) => s + tableFootprintSqm(t), 0));
const freeSqm = computed(() => Math.max(0, totalSqm.value - zonesSqm.value - tablesSqm.value));

// Three.js state
let scene, camera, renderer, controls, animId;
let interactiveMeshes = [];
let groupRefs = { table: {}, zone: {} };
const raycaster = new THREE.Raycaster();
const pointer = new THREE.Vector2();
const floorPlane = new THREE.Plane(new THREE.Vector3(0, 1, 0), 0);
let dragTarget = null;
let dragOffset = new THREE.Vector3();
let pointerDownPos = { x: 0, y: 0 };

const statusColorHex = (table) => {
    if (table.current_order?.status === 'bill_printed') return 0x0284c7; // sky-600
    switch (table.status) {
        case 'occupied': return 0xf59e0b; // amber-500
        case 'cleaning': return 0xeab308; // yellow-500
        case 'reserved': return 0xa855f7; // purple-500
        default: return 0x10b981; // emerald-500
    }
};

const makeLabelSprite = (text, subtext = '', bg = '#0f172a') => {
    const canvas = document.createElement('canvas');
    canvas.width = 256;
    canvas.height = 96;
    const ctx = canvas.getContext('2d');

    ctx.fillStyle = bg;
    ctx.beginPath();
    ctx.roundRect(8, 8, 240, 80, 18);
    ctx.fill();

    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 28px sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText(text, 128, subtext ? 44 : 58);

    if (subtext) {
        ctx.fillStyle = '#cbd5e1';
        ctx.font = 'bold 20px sans-serif';
        ctx.fillText(subtext, 128, 74);
    }

    const tex = new THREE.CanvasTexture(canvas);
    const mat = new THREE.SpriteMaterial({ map: tex, depthTest: false });
    const sprite = new THREE.Sprite(mat);
    sprite.scale.set(1.8, 0.68, 1);
    sprite.renderOrder = 10;
    return sprite;
};

const makeRoundedRectGeo = (w, d, h, radius = 0.18) => {
    const hw = w / 2;
    const hd = d / 2;
    const r = Math.min(radius, hw * 0.45, hd * 0.45);
    const shape = new THREE.Shape();
    shape.moveTo(-hw + r, -hd);
    shape.lineTo(hw - r, -hd);
    shape.quadraticCurveTo(hw, -hd, hw, -hd + r);
    shape.lineTo(hw, hd - r);
    shape.quadraticCurveTo(hw, hd, hw - r, hd);
    shape.lineTo(-hw + r, hd);
    shape.quadraticCurveTo(-hw, hd, -hw, hd - r);
    shape.lineTo(-hw, -hd + r);
    shape.quadraticCurveTo(-hw, -hd, -hw + r, -hd);
    const geo = new THREE.ExtrudeGeometry(shape, { depth: h, bevelEnabled: false });
    geo.rotateX(Math.PI / 2);
    return geo;
};

const addFloralCenterpiece = (group, yTop) => {
    const vase = new THREE.Mesh(
        new THREE.CylinderGeometry(0.06, 0.045, 0.14, 12),
        new THREE.MeshStandardMaterial({ color: 0xf8fafc, roughness: 0.3 })
    );
    vase.position.y = yTop + 0.07;
    group.add(vase);

    const foliage = new THREE.Mesh(
        new THREE.DodecahedronGeometry(0.11, 0),
        new THREE.MeshStandardMaterial({ color: 0x15803d, roughness: 0.8 })
    );
    foliage.position.y = yTop + 0.18;
    group.add(foliage);

    const bloomColors = [0xf43f5e, 0xfbbf24, 0xec4899];
    bloomColors.forEach((c, idx) => {
        const bloom = new THREE.Mesh(
            new THREE.SphereGeometry(0.045, 8, 8),
            new THREE.MeshStandardMaterial({ color: c, roughness: 0.5 })
        );
        const a = (idx / bloomColors.length) * Math.PI * 2;
        bloom.position.set(Math.cos(a) * 0.07, yTop + 0.24, Math.sin(a) * 0.07);
        group.add(bloom);
    });
};

const rebuildScene = () => {
    if (!scene) return;
    while (scene.children.length > 0) {
        scene.remove(scene.children[0]);
    }
    interactiveMeshes = [];
    groupRefs = { table: {}, zone: {} };

    const W = areaWidth.value;
    const L = areaLength.value;

    // Lights
    scene.add(new THREE.AmbientLight(0xffffff, 0.75));
    const dir = new THREE.DirectionalLight(0xffffff, 0.85);
    dir.position.set(W * 0.7, 16, L * 0.8);
    dir.castShadow = true;
    dir.shadow.mapSize.set(1024, 1024);
    scene.add(dir);

    // Floor Slab (centered at W/2, L/2)
    const floorGeo = new THREE.BoxGeometry(W, 0.1, L);
    const floorMat = new THREE.MeshStandardMaterial({ color: 0xe2e8f0, roughness: 0.9 });
    const floor = new THREE.Mesh(floorGeo, floorMat);
    floor.position.set(W / 2, -0.05, L / 2);
    floor.receiveShadow = true;
    scene.add(floor);

    // 1m Grid lines
    const gridHelper = new THREE.GridHelper(Math.max(W, L), Math.max(W, L), 0x94a3b8, 0xcbd5e1);
    gridHelper.position.set(W / 2, 0.005, L / 2);
    scene.add(gridHelper);

    // Subtle Perimeter Walls (back & left)
    const wallMat = new THREE.MeshStandardMaterial({ color: 0x334155, roughness: 0.8 });
    const backWall = new THREE.Mesh(new THREE.BoxGeometry(W, 1.4, 0.12), wallMat);
    backWall.position.set(W / 2, 0.7, -0.06);
    scene.add(backWall);

    const leftWall = new THREE.Mesh(new THREE.BoxGeometry(0.12, 1.4, L), wallMat);
    leftWall.position.set(-0.06, 0.7, L / 2);
    scene.add(leftWall);

    // Zones (Counter, Bar, Kitchen, Entrance, Floral)
    localZones.value.forEach(z => {
        const group = new THREE.Group();
        group.position.set(z.pos_x, 0, z.pos_z);
        group.userData = { kind: 'zone', id: z.id };

        if (z.zone_type === 'floral') {
            const radius = Math.min(z.width_m, z.depth_m) * 0.42;
            const pot = new THREE.Mesh(
                new THREE.CylinderGeometry(radius, radius * 0.72, 0.55, 16),
                new THREE.MeshStandardMaterial({ color: 0xb45309, roughness: 0.7 })
            );
            pot.position.y = 0.275;
            pot.castShadow = true;
            pot.userData = { kind: 'zone', id: z.id };
            group.add(pot);
            interactiveMeshes.push(pot);

            const bush = new THREE.Mesh(
                new THREE.DodecahedronGeometry(radius * 1.15, 1),
                new THREE.MeshStandardMaterial({ color: 0x16a34a, roughness: 0.8 })
            );
            bush.position.y = 0.75;
            bush.castShadow = true;
            bush.userData = { kind: 'zone', id: z.id };
            group.add(bush);
            interactiveMeshes.push(bush);

            [0xf43f5e, 0xfacc15, 0xfb7185, 0xc084fc].forEach((c, idx) => {
                const fl = new THREE.Mesh(
                    new THREE.SphereGeometry(radius * 0.24, 8, 8),
                    new THREE.MeshStandardMaterial({ color: c, roughness: 0.5 })
                );
                const a = (idx / 4) * Math.PI * 2;
                fl.position.set(Math.cos(a) * radius * 0.65, 0.92, Math.sin(a) * radius * 0.65);
                group.add(fl);
            });

            const label = makeLabelSprite(z.name, `${(z.width_m * z.depth_m).toFixed(1)} m²`, '#14532d');
            label.position.set(0, 1.55, 0);
            group.add(label);

            scene.add(group);
            groupRefs.zone[z.id] = group;
            return;
        }

        const h = z.zone_type === 'entrance' ? 0.08 : 1.05;
        const zoneColor = z.zone_type === 'bar' ? 0x4f46e5 : z.zone_type === 'kitchen' ? 0xd97706 : z.zone_type === 'entrance' ? 0x059669 : 0x1e293b;
        const box = new THREE.Mesh(
            new THREE.BoxGeometry(z.width_m, h, z.depth_m),
            new THREE.MeshStandardMaterial({ color: zoneColor, roughness: 0.5 })
        );
        box.position.y = h / 2;
        box.castShadow = true;
        box.receiveShadow = true;
        box.userData = { kind: 'zone', id: z.id };
        group.add(box);

        if (selectedZoneId.value === z.id && editMode.value) {
            const ring = new THREE.Mesh(
                new THREE.RingGeometry(Math.max(z.width_m, z.depth_m) * 0.55, Math.max(z.width_m, z.depth_m) * 0.62, 32),
                new THREE.MeshBasicMaterial({ color: 0xfacc15, side: THREE.DoubleSide })
            );
            ring.rotation.x = -Math.PI / 2;
            ring.position.y = 0.03;
            group.add(ring);
        }

        const label = makeLabelSprite(z.name, `${(z.width_m * z.depth_m).toFixed(1)} m²`, '#1e293b');
        label.position.set(0, h + 0.65, 0);
        group.add(label);

        scene.add(group);
        groupRefs.zone[z.id] = group;
        interactiveMeshes.push(box);
    });

    // Tables + Chairs
    localTables.value.forEach(t => {
        const group = new THREE.Group();
        group.position.set(t.pos_x, 0, t.pos_z);
        group.rotation.y = (Number(t.rotation_deg || 0) * Math.PI) / 180;
        group.userData = { kind: 'table', id: t.id };

        const isHigh = t.shape === 'high_table';
        const topY = isHigh ? 1.05 : 0.75;
        const color = statusColorHex(t);
        const topMat = new THREE.MeshStandardMaterial({ color, roughness: 0.4 });
        const legMat = new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.6 });

        let topMesh;
        if (t.shape === 'round' || isHigh) {
            topMesh = new THREE.Mesh(new THREE.CylinderGeometry(t.width_m / 2, t.width_m / 2, 0.08, 28), topMat);
            topMesh.position.y = topY;
        } else if (t.shape === 'rounded') {
            topMesh = new THREE.Mesh(makeRoundedRectGeo(t.width_m, t.depth_m, 0.08, 0.22), topMat);
            topMesh.position.y = topY + 0.04;
        } else {
            topMesh = new THREE.Mesh(new THREE.BoxGeometry(t.width_m, 0.08, t.depth_m), topMat);
            topMesh.position.y = topY;
        }
        topMesh.castShadow = true;
        topMesh.userData = { kind: 'table', id: t.id };
        group.add(topMesh);

        // Central Pedestal Leg
        const legH = topY - 0.04;
        const leg = new THREE.Mesh(new THREE.CylinderGeometry(0.08, 0.22, legH, 12), legMat);
        leg.position.y = legH / 2;
        leg.castShadow = true;
        leg.userData = { kind: 'table', id: t.id };
        group.add(leg);
        interactiveMeshes.push(leg);

        // Decorated floral centerpiece (always on High Table, optional on others)
        if (isHigh || t.has_floral) {
            addFloralCenterpiece(group, topY + 0.04);
        }

        // Invisible floor hit pad for easy mouse grabbing
        const hitPad = new THREE.Mesh(
            new THREE.BoxGeometry(t.width_m + 0.6, 0.05, t.depth_m + 0.6),
            new THREE.MeshBasicMaterial({ transparent: true, opacity: 0, depthWrite: false })
        );
        hitPad.position.y = 0.025;
        hitPad.userData = { kind: 'table', id: t.id };
        group.add(hitPad);
        interactiveMeshes.push(hitPad);

        // Chairs arranged around table
        const seats = Math.min(12, Math.max(1, Number(t.seating_capacity || 4)));
        const chairMat = new THREE.MeshStandardMaterial({ color: 0x475569, roughness: 0.6 });
        for (let i = 0; i < seats; i++) {
            const angle = (i / seats) * Math.PI * 2;
            const rx = (t.width_m / 2) + 0.32;
            const rz = (t.depth_m / 2) + 0.32;
            const chair = new THREE.Mesh(new THREE.BoxGeometry(0.36, 0.45, 0.36), chairMat);
            chair.position.set(Math.cos(angle) * rx, 0.225, Math.sin(angle) * rz);
            chair.rotation.y = -angle;
            chair.castShadow = true;
            chair.userData = { kind: 'table', id: t.id };
            group.add(chair);
            interactiveMeshes.push(chair);
        }

        // Selection ring in Edit Mode
        if (selectedTableId.value === t.id && editMode.value) {
            const ring = new THREE.Mesh(
                new THREE.RingGeometry(Math.max(t.width_m, t.depth_m) * 0.75, Math.max(t.width_m, t.depth_m) * 0.85, 32),
                new THREE.MeshBasicMaterial({ color: 0xfacc15, side: THREE.DoubleSide })
            );
            ring.rotation.x = -Math.PI / 2;
            ring.position.y = 0.03;
            group.add(ring);
        }

        const sub = t.current_order ? `UGX ${Math.round(t.current_order.total_amount_ugx).toLocaleString()}` : `${seats} seats`;
        const label = makeLabelSprite(t.table_number, sub);
        label.position.set(0, 1.45, 0);
        group.add(label);

        scene.add(group);
        groupRefs.table[t.id] = group;
        interactiveMeshes.push(topMesh);
    });
};

const resetCamera = () => {
    if (!camera || !controls) return;
    const W = areaWidth.value;
    const L = areaLength.value;
    camera.position.set(W * 0.5, Math.max(W, L) * 0.85, L * 1.25);
    controls.target.set(W / 2, 0, L / 2);
    controls.update();
};

const getFloorHit = (clientX, clientY) => {
    const rect = renderer.domElement.getBoundingClientRect();
    pointer.x = ((clientX - rect.left) / rect.width) * 2 - 1;
    pointer.y = -((clientY - rect.top) / rect.height) * 2 + 1;
    raycaster.setFromCamera(pointer, camera);
    const hit = new THREE.Vector3();
    raycaster.ray.intersectPlane(floorPlane, hit);
    return hit;
};

const onPointerDown = (e) => {
    pointerDownPos = { x: e.clientX, y: e.clientY };
    const rect = renderer.domElement.getBoundingClientRect();
    pointer.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
    pointer.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
    raycaster.setFromCamera(pointer, camera);

    const hits = raycaster.intersectObjects(interactiveMeshes, false);
    if (hits.length > 0 && editMode.value) {
        e.stopPropagation();
        e.preventDefault();
        const { kind, id } = hits[0].object.userData;
        controls.enabled = false;
        if (kind === 'table') {
            selectedTableId.value = id;
            selectedZoneId.value = null;
            dragTarget = { kind, item: localTables.value.find(t => t.id === id), group: groupRefs.table[id] };
        } else if (kind === 'zone') {
            selectedZoneId.value = id;
            selectedTableId.value = null;
            dragTarget = { kind, item: localZones.value.find(z => z.id === id), group: groupRefs.zone[id] };
        }
        const floorPt = getFloorHit(e.clientX, e.clientY);
        if (dragTarget?.item) {
            dragOffset.set(dragTarget.item.pos_x - floorPt.x, 0, dragTarget.item.pos_z - floorPt.z);
        }
        rebuildScene();
        // Re-bind group reference after rebuildScene
        if (dragTarget?.item) {
            dragTarget.group = dragTarget.kind === 'table'
                ? groupRefs.table[dragTarget.item.id]
                : groupRefs.zone[dragTarget.item.id];
        }
    }
};

const onPointerMove = (e) => {
    if (!dragTarget?.item || !editMode.value) {
        if (renderer?.domElement) {
            const rect = renderer.domElement.getBoundingClientRect();
            pointer.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
            pointer.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
            raycaster.setFromCamera(pointer, camera);
            const hits = raycaster.intersectObjects(interactiveMeshes, false);
            renderer.domElement.style.cursor = hits.length > 0 ? (editMode.value ? 'move' : 'pointer') : 'grab';
        }
        return;
    }
    const floorPt = getFloorHit(e.clientX, e.clientY);
    const snap = (v) => Math.round(v * 2) / 2; // 0.5m snap
    const nx = Math.max(0.5, Math.min(areaWidth.value - 0.5, snap(floorPt.x + dragOffset.x)));
    const nz = Math.max(0.5, Math.min(areaLength.value - 0.5, snap(floorPt.z + dragOffset.z)));
    dragTarget.item.pos_x = nx;
    dragTarget.item.pos_z = nz;
    if (dragTarget.group) {
        dragTarget.group.position.set(nx, 0, nz);
    }
};

const onPointerUp = (e) => {
    if (dragTarget) {
        dragTarget = null;
        controls.enabled = true;
        return;
    }

    const moved = Math.hypot(e.clientX - pointerDownPos.x, e.clientY - pointerDownPos.y);
    if (moved < 6 && !editMode.value) {
        const rect = renderer.domElement.getBoundingClientRect();
        pointer.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
        pointer.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
        raycaster.setFromCamera(pointer, camera);
        const hits = raycaster.intersectObjects(interactiveMeshes, false);
        if (hits.length > 0 && hits[0].object.userData.kind === 'table') {
            const tbl = props.area.tables.find(t => t.id === hits[0].object.userData.id);
            if (tbl) emit('select-table', tbl);
        }
    }
};

const onTableTypeChange = () => {
    if (selectedTable.value?.shape === 'high_table') {
        selectedTable.value.has_floral = true;
    }
    rebuildScene();
};

const syncSelectedPosition = () => {
    if (selectedTable.value) {
        const g = groupRefs.table[selectedTable.value.id];
        if (g) g.position.set(selectedTable.value.pos_x, 0, selectedTable.value.pos_z);
    } else if (selectedZone.value) {
        const g = groupRefs.zone[selectedZone.value.id];
        if (g) g.position.set(selectedZone.value.pos_x, 0, selectedZone.value.pos_z);
    }
};

const nudgeSelected = (dx, dz) => {
    const item = selectedTable.value || selectedZone.value;
    if (!item) return;
    item.pos_x = Math.max(0.5, Math.min(areaWidth.value - 0.5, Math.round((item.pos_x + dx) * 2) / 2));
    item.pos_z = Math.max(0.5, Math.min(areaLength.value - 0.5, Math.round((item.pos_z + dz) * 2) / 2));
    syncSelectedPosition();
};

let drag2D = null;
const start2DDrag = (kind, item, ev) => {
    if (kind === 'table') {
        selectedTableId.value = item.id;
        selectedZoneId.value = null;
    } else {
        selectedZoneId.value = item.id;
        selectedTableId.value = null;
    }
    drag2D = { kind, item };
    rebuildScene();

    const onMove2D = (e) => {
        if (!drag2D || !map2dRef.value) return;
        const rect = map2dRef.value.getBoundingClientRect();
        const rx = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
        const rz = Math.max(0, Math.min(1, (e.clientY - rect.top) / rect.height));
        const snap = (v) => Math.round(v * 2) / 2;
        drag2D.item.pos_x = Math.max(0.5, Math.min(areaWidth.value - 0.5, snap(rx * areaWidth.value)));
        drag2D.item.pos_z = Math.max(0.5, Math.min(areaLength.value - 0.5, snap(rz * areaLength.value)));
        syncSelectedPosition();
    };

    const onUp2D = () => {
        drag2D = null;
        window.removeEventListener('pointermove', onMove2D);
        window.removeEventListener('pointerup', onUp2D);
    };

    window.addEventListener('pointermove', onMove2D);
    window.addEventListener('pointerup', onUp2D);
};

const toggleEditMode = () => {
    editMode.value = !editMode.value;
    rebuildScene();
};

const rotateSelectedTable = () => {
    if (!selectedTable.value) return;
    selectedTable.value.rotation_deg = (Number(selectedTable.value.rotation_deg || 0) + 45) % 360;
    rebuildScene();
};

const addZone = async () => {
    const { data } = await axios.post('/restaurant/zones', {
        area_id: props.area.id,
        name: newZone.name || 'Counter',
        zone_type: newZone.zone_type,
        width_m: newZone.width_m || 3.5,
        depth_m: newZone.depth_m || 1.2,
        pos_x: 2.5,
        pos_z: 1.5,
    });
    localZones.value.push({
        ...data.zone,
        pos_x: Number(data.zone.pos_x),
        pos_z: Number(data.zone.pos_z),
        width_m: Number(data.zone.width_m),
        depth_m: Number(data.zone.depth_m),
    });
    selectedZoneId.value = data.zone.id;
    rebuildScene();
};

const deleteZone = async (zone) => {
    await axios.delete(`/restaurant/zones/${zone.id}`);
    localZones.value = localZones.value.filter(z => z.id !== zone.id);
    selectedZoneId.value = null;
    rebuildScene();
};

const saveLayout = async () => {
    isSaving.value = true;
    try {
        await axios.post(`/restaurant/areas/${props.area.id}/layout`, {
            width_m: areaWidth.value,
            length_m: areaLength.value,
            tables: localTables.value.map(t => ({
                id: t.id,
                pos_x: t.pos_x,
                pos_z: t.pos_z,
                width_m: t.width_m,
                depth_m: t.depth_m,
                rotation_deg: t.rotation_deg,
                shape: t.shape,
                has_floral: t.shape === 'high_table' ? true : Boolean(t.has_floral),
            })),
            zones: localZones.value.map(z => ({
                id: z.id,
                pos_x: z.pos_x,
                pos_z: z.pos_z,
                width_m: z.width_m,
                depth_m: z.depth_m,
            })),
        });
        editMode.value = false;
        emit('layout-saved');
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to save 3D layout.');
    } finally {
        isSaving.value = false;
    }
};

watch(() => props.area, (newArea) => {
    areaWidth.value = Number(newArea.width_m || 15);
    areaLength.value = Number(newArea.length_m || 12.33);
    localTables.value = (newArea.tables || []).map(t => ({
        ...t,
        pos_x: Number(t.pos_x ?? 2),
        pos_z: Number(t.pos_z ?? 2),
        width_m: Number(t.width_m ?? 1.2),
        depth_m: Number(t.depth_m ?? 1.2),
        rotation_deg: Number(t.rotation_deg ?? 0),
        shape: t.shape || 'rect',
    }));
    localZones.value = (newArea.zones || []).map(z => ({
        ...z,
        pos_x: Number(z.pos_x ?? 2),
        pos_z: Number(z.pos_z ?? 1),
        width_m: Number(z.width_m ?? 3.5),
        depth_m: Number(z.depth_m ?? 1.2),
    }));
    rebuildScene();
    resetCamera();
}, { deep: true });

onMounted(() => {
    const host = canvasHost.value;
    scene = new THREE.Scene();
    scene.background = new THREE.Color(0x0f172a);

    camera = new THREE.PerspectiveCamera(45, host.clientWidth / host.clientHeight, 0.1, 200);
    renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(host.clientWidth, host.clientHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.shadowMap.enabled = true;
    host.appendChild(renderer.domElement);

    controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.maxPolarAngle = Math.PI / 2.15;
    controls.minDistance = 4;
    controls.maxDistance = 60;

    renderer.domElement.addEventListener('pointerdown', onPointerDown, { capture: true });
    window.addEventListener('pointermove', onPointerMove);
    window.addEventListener('pointerup', onPointerUp);
    window.addEventListener('pointercancel', onPointerUp);

    rebuildScene();
    resetCamera();

    const animate = () => {
        animId = requestAnimationFrame(animate);
        controls.update();
        renderer.render(scene, camera);
    };
    animate();
});

onUnmounted(() => {
    if (animId) cancelAnimationFrame(animId);
    window.removeEventListener('pointermove', onPointerMove);
    window.removeEventListener('pointerup', onPointerUp);
    window.removeEventListener('pointercancel', onPointerUp);
    if (renderer) renderer.dispose();
});
</script>

