<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { CarouselApi } from '@/components/ui/carousel';
import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from '@/components/ui/carousel';
import { Drawer, DrawerClose, DrawerContent } from '@/components/ui/drawer';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { DataQueryType } from '@/types/data-query';
import { DATA_QUERY_OPTIONS } from '@/types/data-query';
import {
    AlertCircle,
    BarChart,
    Calendar,
    Clock,
    CloudOff,
    TrendingUp,
} from 'lucide-vue-next';
import { computed, defineComponent, h, onMounted, ref } from 'vue';

const iconComponents: Record<string, any> = {
    clock: Clock,
    calendar: Calendar,
    'trending-up': TrendingUp,
    'alert-circle': AlertCircle,
    'bar-chart': BarChart,
};

interface Props {
    selectedCount: number;
    selectedDataQuery: DataQueryType | null;
    isLoadingResults: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'select-data-query', type: DataQueryType): void;
    (e: 'go-to-map'): void;
    (e: 'proceed-with-data-query'): void;
}>();

// Error Drawer state
const showOutageDrawer = ref(false);
const showDateRangeDrawer = ref(false);
const carouselApi = ref<CarouselApi | null>(null);
const startDate = ref('');
const endDate = ref('');
const tweenFactor = ref(0);
const tweenNodes = ref<HTMLElement[]>([]);

const requiresDateRange = computed(
    () =>
        DATA_QUERY_OPTIONS.find((o) => o.type === props.selectedDataQuery)
            ?.requiresDateRange ?? false,
);

const DateRangeFields = defineComponent({
    name: 'DateRangeFields',
    props: {
        start: {
            type: String,
            required: true,
        },
        end: {
            type: String,
            required: true,
        },
        onUpdateStart: {
            type: Function,
            required: true,
        },
        onUpdateEnd: {
            type: Function,
            required: true,
        },
    },
    setup(props) {
        return () =>
            h('div', { class: 'grid grid-cols-1 gap-4 md:grid-cols-2' }, [
                h('div', { class: 'space-y-2' }, [
                    h(
                        Label,
                        {
                            for: 'start-date',
                        },
                        { default: () => 'Startdatum' },
                    ),
                    h('input', {
                        id: 'start-date',
                        type: 'date',
                        class: 'w-full rounded-md border border-border bg-input p-2',
                        value: props.start,
                        onInput: (event: Event) => {
                            const target = event.target as HTMLInputElement;
                            (props.onUpdateStart as (value: string) => void)(
                                target.value,
                            );
                        },
                    }),
                ]),
                h('div', { class: 'space-y-2' }, [
                    h(
                        Label,
                        {
                            for: 'end-date',
                        },
                        { default: () => 'Enddatum' },
                    ),
                    h('input', {
                        id: 'end-date',
                        type: 'date',
                        class: 'w-full rounded-md border border-border bg-input p-2',
                        value: props.end,
                        onInput: (event: Event) => {
                            const target = event.target as HTMLInputElement;
                            (props.onUpdateEnd as (value: string) => void)(
                                target.value,
                            );
                        },
                    }),
                ]),
            ]);
    },
});

// Intercept proceed emission to allow UI error handling based on fetch errors
function handleProceed() {
    // Emit and expect parent to perform fetch; event contract: parent dispatches
    // technical event on error: `new CustomEvent('aemet:outage', { detail: { status, type } })`
    emit('proceed-with-data-query');
}

function attachOutageListener() {
    const listener = () => {
        showOutageDrawer.value = true;
    };
    window.addEventListener('aemet:outage', listener);
}

attachOutageListener();

function syncSelectionFromCarousel(api: CarouselApi | null) {
    if (!api) {
        return;
    }

    const index = api.selectedScrollSnap();
    const actualIndex = index % DATA_QUERY_OPTIONS.length;
    const option = DATA_QUERY_OPTIONS[actualIndex];

    if (option && option.type !== props.selectedDataQuery) {
        emit('select-data-query', option.type);
    }
}

function setCarouselApi(api: CarouselApi) {
    carouselApi.value = api;
    syncSelectionFromCarousel(api);
    api.on('select', () => syncSelectionFromCarousel(api));
    api.on('scroll', setTweenValues);
    api.on('reInit', setTweenValues);
}

function setStartDate(value: string) {
    startDate.value = value;
}

function setEndDate(value: string) {
    endDate.value = value;
}

function handleCardClick(optionType: DataQueryType, index: number) {
    emit('select-data-query', optionType);
    if (carouselApi.value) {
        carouselApi.value.scrollTo(index);
    }
}

const TWEEN_FACTOR_BASE = 0.5;

function numberWithinRange(number: number, min: number, max: number): number {
    return Math.min(Math.max(number, min), max);
}

function setTweenValues() {
    if (!carouselApi.value) return;

    const engine = carouselApi.value.internalEngine();
    const scrollProgress = carouselApi.value.scrollProgress();
    const slidesInView = carouselApi.value.slidesInView();
    const isScrollEvent = engine.dragHandler.pointerDown();

    carouselApi.value.scrollSnapList().forEach((scrollSnap, snapIndex) => {
        let diffToTarget = scrollSnap - scrollProgress;
        const slidesInSnap = engine.slideRegistry[snapIndex];

        slidesInSnap.forEach((slideIndex) => {
            if (isScrollEvent && !slidesInView.includes(slideIndex)) return;

            if (engine.options.loop) {
                engine.slideLooper.loopPoints.forEach((loopItem) => {
                    const target = loopItem.target();

                    if (slideIndex === loopItem.index && target !== 0) {
                        const sign = Math.sign(target);

                        if (sign === -1) {
                            diffToTarget = scrollSnap - (1 + scrollProgress);
                        }
                        if (sign === 1) {
                            diffToTarget = scrollSnap + (1 - scrollProgress);
                        }
                    }
                });
            }

            const tweenValue = 1 - Math.abs(diffToTarget * tweenFactor.value);
            const scale = numberWithinRange(tweenValue, 0.85, 1).toString();
            const opacity = numberWithinRange(tweenValue, 0.5, 1).toString();
            const slideNode = tweenNodes.value[slideIndex];

            if (slideNode) {
                slideNode.style.transform = `scale(${scale})`;
                slideNode.style.opacity = opacity;
            }
        });
    });
}

function setTweenFactor() {
    if (!carouselApi.value) return;
    tweenFactor.value =
        TWEEN_FACTOR_BASE * carouselApi.value.scrollSnapList().length;
}

onMounted(() => {
    if (carouselApi.value) {
        setTweenFactor();
        setTweenValues();
        carouselApi.value.on('reInit', setTweenFactor);
    }
});
</script>

<template>
    <section class="min-h-screen px-4 pt-6 pb-10 sm:px-8">
        <div class="mx-auto max-w-5xl space-y-8">
            <div>
                <h2 class="mb-2 text-3xl font-bold">
                    Welche Daten möchten Sie abfragen?
                </h2>
                <p class="text-slate-600 dark:text-slate-400">
                    Wählen Sie die Art der Daten aus, die Sie für Ihre
                    {{ selectedCount }} ausgewählte{{
                        selectedCount !== 1 ? 'n' : ''
                    }}
                    Station{{ selectedCount !== 1 ? 'en' : '' }} abfragen
                    möchten.
                </p>
            </div>

            <div class="px-12">
                <Carousel
                    v-slot="{ canScrollNext, canScrollPrev }"
                    class="relative w-full"
                    :opts="{
                        align: 'center',
                        loop: true,
                    }"
                    @init-api="setCarouselApi"
                >
                    <CarouselContent class="-ml-4">
                        <CarouselItem
                            v-for="(option, index) in DATA_QUERY_OPTIONS"
                            :key="option.type"
                            class="basis-full pl-4 lg:basis-1/3"
                        >
                            <div
                                :ref="
                                    (el) => {
                                        if (el)
                                            tweenNodes[index] =
                                                el as HTMLElement;
                                    }
                                "
                                class="h-full p-2"
                            >
                                <Card
                                    class="h-full cursor-pointer border border-border bg-background shadow-md"
                                    @click="handleCardClick(option.type, index)"
                                >
                                    <CardHeader>
                                        <div
                                            class="flex items-start justify-between"
                                        >
                                            <component
                                                :is="
                                                    iconComponents[option.icon]
                                                "
                                                class="h-8 w-8 text-foreground"
                                            />
                                            <div
                                                v-if="option.quickWin"
                                                class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400"
                                            >
                                                Quick Win
                                            </div>
                                        </div>
                                        <CardTitle class="mt-4">
                                            {{ option.title }}
                                        </CardTitle>
                                        <CardDescription>
                                            {{ option.description }}
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div
                                            class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400"
                                        >
                                            <Clock class="h-4 w-4" />
                                            <span>{{
                                                option.estimatedTime
                                            }}</span>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </CarouselItem>
                    </CarouselContent>

                    <CarouselPrevious
                        v-if="canScrollPrev"
                        class="top-1/2 -left-12 -translate-y-1/2"
                        aria-label="Vorherige Optionen"
                    />
                    <CarouselNext
                        v-if="canScrollNext"
                        class="top-1/2 -right-12 -translate-y-1/2"
                        aria-label="Nächste Optionen"
                    />
                </Carousel>
            </div>

            <div
                v-if="props.selectedDataQuery && requiresDateRange"
                class="mt-8"
            >
                <Card class="hidden md:block">
                    <CardHeader>
                        <CardTitle>Zeitraum auswählen</CardTitle>
                        <CardDescription
                            >Wählen Sie den gewünschten Zeitraum für Ihre
                            Datenabfrage.</CardDescription
                        >
                    </CardHeader>
                    <CardContent>
                        <DateRangeFields
                            :start="startDate"
                            :end="endDate"
                            :on-update-start="setStartDate"
                            :on-update-end="setEndDate"
                        />
                    </CardContent>
                </Card>

                <div class="md:hidden">
                    <Button
                        variant="outline"
                        class="w-full"
                        @click="showDateRangeDrawer = true"
                    >
                        Zeitraum auswählen
                    </Button>
                </div>
            </div>

            <Drawer v-model:open="showDateRangeDrawer" class="md:hidden">
                <DrawerContent>
                    <div class="mx-auto w-full max-w-md space-y-4 px-6 py-6">
                        <div class="space-y-1 text-center">
                            <h3 class="text-lg font-semibold">
                                Zeitraum auswählen
                            </h3>
                            <p
                                class="text-sm text-slate-600 dark:text-slate-400"
                            >
                                Wählen Sie den gewünschten Zeitraum für Ihre
                                Datenabfrage.
                            </p>
                        </div>
                        <DateRangeFields
                            :start="startDate"
                            :end="endDate"
                            :on-update-start="setStartDate"
                            :on-update-end="setEndDate"
                        />
                        <div class="flex justify-end">
                            <DrawerClose as-child>
                                <Button variant="secondary">Fertig</Button>
                            </DrawerClose>
                        </div>
                    </div>
                </DrawerContent>
            </Drawer>

            <div
                class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <Button
                    variant="outline"
                    class="w-full sm:w-auto"
                    @click="emit('go-to-map')"
                >
                    Zurück zur Auswahl
                </Button>
                <Button
                    class="w-full sm:w-auto"
                    @click="handleProceed"
                    :disabled="
                        !props.selectedDataQuery ||
                        props.isLoadingResults ||
                        (requiresDateRange && (!startDate || !endDate))
                    "
                >
                    <Spinner
                        v-if="props.isLoadingResults"
                        class="mr-2 h-4 w-4"
                    />
                    {{ props.isLoadingResults ? 'Lädt...' : 'Daten abfragen' }}
                </Button>
            </div>
        </div>

        <!-- Outage Drawer -->
        <Drawer v-model:open="showOutageDrawer">
            <DrawerContent>
                <div class="mx-auto w-full max-w-md px-8 py-12">
                    <div class="flex flex-col items-center gap-8 text-center">
                        <CloudOff
                            class="h-20 w-20 text-blue-600 dark:text-blue-400"
                        />

                        <div class="space-y-3">
                            <h2
                                class="text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-50"
                            >
                                Der Spanische Wetterdienst macht gerade Siesta
                            </h2>
                            <p
                                class="text-lg text-slate-600 dark:text-slate-400"
                            >
                                Die API des Spanischen Wetterdiensts (AEMET) ist
                                derzeit nicht erreichbar. Das passiert
                                einigermassen oft, leider. Bite versuche es in
                                ca. 10 Minuten erneut.
                            </p>
                        </div>

                        <DrawerClose as-child>
                            <Button size="lg" class="mt-4"> Alles klar </Button>
                        </DrawerClose>
                    </div>
                </div>
            </DrawerContent>
        </Drawer>
    </section>
</template>
