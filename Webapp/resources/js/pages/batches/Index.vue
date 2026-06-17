<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Layers, MoreHorizontal, Plus, Search } from '@lucide/vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dashboard } from '@/routes';
import {
    create as createBatch,
    destroy as destroyBatch,
    index as batchesIndex,
    show as showBatch,
    edit as editBatch,
} from '@/routes/batches';
import type { InternshipBatch, BreadcrumbItem } from '@/types';

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

defineOptions({
    layout: [],
});

interface Props {
    batches: {
        data: InternshipBatch[];
        links: PaginationLink[];
        meta?: {
            from?: number | null;
            to?: number | null;
            total?: number;
        };
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
    {
        title: 'Batches',
        href: batchesIndex(),
    },
];

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'active':
            return 'default';
        case 'upcoming':
            return 'secondary';
        case 'closed':
            return 'destructive';
        case 'archived':
            return 'outline';
        default:
            return 'default';
    }
};

const getStatusLabel = (status: string) => {
    switch (status) {
        case 'active':
            return 'Active';
        case 'upcoming':
            return 'Upcoming';
        case 'closed':
            return 'Closed';
        case 'archived':
            return 'Archived';
        default:
            return status;
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getOccupancyPercent = (count: number, capacity: number) => {
    if (capacity <= 0) {
        return 0;
    }

    return Math.round((count / capacity) * 100);
};

const getOccupancyColor = (count: number, capacity: number) => {
    if (capacity <= 0) {
        return 'bg-muted-foreground/40';
    }

    const ratio = count / capacity;
    if (ratio >= 1) return 'bg-destructive';
    if (ratio >= 0.8) return 'bg-orange-500';
    return 'bg-primary';
};

const getPaginationLabel = (label: string) =>
    label.replace('&laquo;', '').replace('&raquo;', '').trim();

const paginationLinks = () =>
    props.batches.links.filter((link) => getPaginationLabel(link.label) !== '');

const closeBatchUrl = (batchId: string) => `/batches/${batchId}/close`;

const closeBatch = (batch: InternshipBatch) => {
    if (confirm(`Close ${batch.name}?`)) {
        router.patch(
            closeBatchUrl(batch.id),
            {},
            {
                preserveScroll: true,
            },
        );
    }
};

const archiveBatch = (batch: InternshipBatch) => {
    if (confirm(`Archive ${batch.name}?`)) {
        router.delete(destroyBatch.url(batch.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Internship Batches" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
            <div
                class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">
                        Internship Batches
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Manage your internship cycles and slots.
                    </p>
                </div>
                <Button as-child>
                    <Link :href="createBatch()">
                        <Plus class="mr-2 h-4 w-4" />
                        Create Batch
                    </Link>
                </Button>
            </div>

            <div class="flex items-center gap-2">
                <div class="relative w-full max-w-sm">
                    <Search
                        class="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground"
                    />
                    <Input
                        type="search"
                        placeholder="Search batches..."
                        class="pl-8"
                    />
                </div>
            </div>

            <div
                class="overflow-hidden rounded-lg border bg-card text-card-foreground"
            >
                <Table>
                    <TableHeader class="bg-muted/40">
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="w-[140px] pl-4">Code</TableHead>
                            <TableHead>Batch</TableHead>
                            <TableHead class="w-[120px]">Status</TableHead>
                            <TableHead class="w-[210px]">Period</TableHead>
                            <TableHead class="w-[180px]">Occupancy</TableHead>
                            <TableHead class="w-[190px]">Coordinator</TableHead>
                            <TableHead class="w-[72px] pr-4 text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="batch in batches.data" :key="batch.id">
                            <TableCell class="pl-4">
                                <span
                                    class="font-mono text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                >
                                    {{ batch.batch_code }}
                                </span>
                            </TableCell>
                            <TableCell class="min-w-[240px]">
                                <div class="flex flex-col gap-1">
                                    <Link
                                        :href="showBatch(batch.id)"
                                        class="leading-none font-medium hover:underline"
                                    >
                                        {{ batch.name }}
                                    </Link>
                                    <span
                                        v-if="batch.description"
                                        class="max-w-[360px] truncate text-xs text-muted-foreground"
                                    >
                                        {{ batch.description }}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge
                                    :variant="
                                        getStatusVariant(batch.virtual_status)
                                    "
                                    class="px-2 py-0.5 text-[11px] font-medium"
                                >
                                    {{ getStatusLabel(batch.virtual_status) }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-col gap-1 text-xs">
                                    <span class="font-medium text-foreground">
                                        {{ formatDate(batch.start_date) }}
                                    </span>
                                    <span class="text-muted-foreground">
                                        to {{ formatDate(batch.end_date) }}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div
                                    class="flex min-w-[140px] flex-col gap-1.5"
                                >
                                    <div
                                        class="flex items-center justify-between text-xs"
                                    >
                                        <span class="font-medium">
                                            {{ batch.interns_count ?? 0 }} /
                                            {{ batch.capacity }}
                                        </span>
                                        <span
                                            :class="
                                                (batch.interns_count ?? 0) >=
                                                batch.capacity
                                                    ? 'text-destructive'
                                                    : 'text-muted-foreground'
                                            "
                                        >
                                            {{
                                                getOccupancyPercent(
                                                    batch.interns_count ?? 0,
                                                    batch.capacity,
                                                )
                                            }}%
                                        </span>
                                    </div>
                                    <div
                                        class="h-1.5 w-full overflow-hidden rounded-full bg-secondary"
                                    >
                                        <div
                                            :class="[
                                                'h-full transition-all duration-500',
                                                getOccupancyColor(
                                                    batch.interns_count ?? 0,
                                                    batch.capacity,
                                                ),
                                            ]"
                                            :style="{
                                                width:
                                                    Math.min(
                                                        getOccupancyPercent(
                                                            batch.interns_count ??
                                                                0,
                                                            batch.capacity,
                                                        ),
                                                        100,
                                                    ) + '%',
                                            }"
                                        />
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div
                                    v-if="batch.coordinator"
                                    class="flex items-center gap-2"
                                >
                                    <div
                                        class="flex h-7 w-7 items-center justify-center rounded-full bg-secondary text-[11px] font-semibold uppercase"
                                    >
                                        {{
                                            batch.coordinator.name.substring(
                                                0,
                                                2,
                                            )
                                        }}
                                    </div>
                                    <span class="text-sm font-medium">
                                        {{ batch.coordinator.name }}
                                    </span>
                                </div>
                                <span
                                    v-else
                                    class="inline-flex rounded-md bg-secondary/60 px-2 py-1 text-xs text-muted-foreground"
                                >
                                    Unassigned
                                </span>
                            </TableCell>
                            <TableCell class="pr-4 text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="h-8 w-8"
                                        >
                                            <MoreHorizontal class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent
                                        align="end"
                                        class="w-44"
                                    >
                                        <DropdownMenuItem as-child>
                                            <Link :href="showBatch(batch.id)">
                                                View Dashboard
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem as-child>
                                            <Link :href="editBatch(batch.id)">
                                                Edit Settings
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-if="batch.status === 'active'"
                                            @select.prevent="closeBatch(batch)"
                                        >
                                            Close Batch
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            class="font-medium text-destructive"
                                            @select.prevent="
                                                archiveBatch(batch)
                                            "
                                        >
                                            Archive Batch
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                        <TableEmpty
                            v-if="batches.data.length === 0"
                            :colspan="7"
                        >
                            <div
                                class="flex flex-col items-center gap-2 text-center"
                            >
                                <Layers
                                    class="h-8 w-8 text-muted-foreground/60"
                                />
                                <div>
                                    <p class="font-medium">No batches found</p>
                                    <p class="text-sm text-muted-foreground">
                                        Create the first internship batch to
                                        start organizing interns.
                                    </p>
                                </div>
                            </div>
                        </TableEmpty>
                    </TableBody>
                </Table>
            </div>

            <div
                v-if="batches.links.length > 3"
                class="flex flex-col gap-3 border-t pt-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-sm text-muted-foreground">
                    Showing {{ batches.meta?.from ?? 0 }} to
                    {{ batches.meta?.to ?? 0 }} of
                    {{ batches.meta?.total ?? 0 }}
                    batches
                </p>
                <div class="flex flex-wrap gap-1">
                    <Button
                        v-for="link in paginationLinks()"
                        :key="link.label"
                        :variant="link.active ? 'default' : 'outline'"
                        size="sm"
                        :disabled="!link.url"
                        as-child
                    >
                        <Link v-if="link.url" :href="link.url">
                            {{ getPaginationLabel(link.label) }}
                        </Link>
                        <span v-else>
                            {{ getPaginationLabel(link.label) }}
                        </span>
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
