<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Search, Upload, X } from '@lucide/vue';
import { computed, reactive } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { AttendanceRecord, Auth, BreadcrumbItem } from '@/types';

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

defineOptions({
    layout: [],
});

interface Props {
    attendances: {
        data: AttendanceRecord[];
        links: PaginationLink[];
        meta?: {
            from?: number | null;
            to?: number | null;
            total?: number;
        };
    };
    filters: {
        search: string;
        date: string;
        status: string;
    };
    stats: {
        checked_in_today: number;
        still_checked_in: number;
        late_today: number;
        total_records: number;
    };
    statuses: {
        label: string;
        value: string;
    }[];
    import_errors: string[];
}

const props = defineProps<Props>();
const page = usePage<{ auth: Auth }>();

const attendanceHref = '/attendances';
const attendanceImportHref = '/attendances/import';
const isAdmin = computed(() => String(page.props.auth.user.role) === 'admin');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
    {
        title: 'Attendance',
        href: attendanceHref,
    },
];

const form = reactive({
    search: props.filters.search,
    date: props.filters.date,
    status: props.filters.status || 'all',
});

const importForm = useForm({
    attendance_file: null as File | null,
});

const hasFilters = computed(
    () => form.search !== '' || form.date !== '' || form.status !== 'all',
);

const applyFilters = () => {
    router.get(
        attendanceHref,
        {
            search: form.search || undefined,
            date: form.date || undefined,
            status: form.status === 'all' ? undefined : form.status,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    form.search = '';
    form.date = '';
    form.status = 'all';
    applyFilters();
};

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'present':
            return 'default';
        case 'late':
            return 'secondary';
        case 'partial':
            return 'outline';
        case 'absent':
            return 'destructive';
        default:
            return 'default';
    }
};

const formatDate = (value: string | null) => {
    if (!value) return 'Not recorded';

    return new Date(value).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatTime = (value: string | null) => {
    if (!value) return 'Not recorded';

    return new Date(value).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatDuration = (minutes: number | null) => {
    if (minutes === null) return 'In progress';

    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;

    if (hours === 0) {
        return `${remainingMinutes}m`;
    }

    return `${hours}h ${remainingMinutes}m`;
};

const getPaginationLabel = (label: string) =>
    label.replace('&laquo;', '').replace('&raquo;', '').trim();

const paginationLinks = () =>
    props.attendances.links.filter(
        (link) => getPaginationLabel(link.label) !== '',
    );

const handleAttendanceFile = (event: Event) => {
    const input = event.target as HTMLInputElement;
    importForm.attendance_file = input.files?.[0] ?? null;
};

const importAttendance = () => {
    importForm.post(attendanceImportHref, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            importForm.reset();
            importForm.clearErrors();
        },
    });
};
</script>

<template>
    <Head title="Attendance" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
            <div
                class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">
                        Attendance
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Review intern check-ins and check-outs.
                    </p>
                </div>
                <Dialog v-if="isAdmin">
                    <DialogTrigger as-child>
                        <Button>
                            <Upload class="mr-2 h-4 w-4" />
                            Import CSV
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Import Attendance</DialogTitle>
                            <DialogDescription>
                                Required columns: date, intern_email,
                                check_in_time.
                            </DialogDescription>
                        </DialogHeader>
                        <form
                            class="grid gap-4"
                            @submit.prevent="importAttendance"
                        >
                            <div class="grid gap-2">
                                <Label for="attendance_file">CSV file</Label>
                                <Input
                                    id="attendance_file"
                                    type="file"
                                    accept=".csv,text/csv"
                                    :class="{
                                        'border-destructive':
                                            importForm.errors.attendance_file,
                                    }"
                                    @change="handleAttendanceFile"
                                />
                                <p
                                    v-if="importForm.errors.attendance_file"
                                    class="text-xs text-destructive"
                                >
                                    {{ importForm.errors.attendance_file }}
                                </p>
                            </div>
                            <div class="grid gap-2">
                                <p class="text-xs text-muted-foreground">
                                    Use YYYY-MM-DD dates and 24-hour times.
                                </p>
                                <pre
                                    class="max-w-full overflow-x-auto rounded-md bg-muted p-3 text-xs whitespace-pre"
                                ><code>date,intern_email,check_in_time,check_out_time,wifi_ssid,activities
2026-06-24,victor@example.com,09:30,17:00,BJUKA_WIFI,Worked on Laravel tasks</code></pre>
                            </div>
                            <DialogFooter>
                                <Button
                                    type="submit"
                                    :disabled="
                                        importForm.processing ||
                                        !importForm.attendance_file
                                    "
                                >
                                    {{
                                        importForm.processing
                                            ? 'Importing...'
                                            : 'Import'
                                    }}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div
                v-if="import_errors.length > 0"
                class="rounded-lg border border-destructive/30 bg-destructive/5 p-4"
            >
                <p class="text-sm font-medium text-destructive">
                    Import row errors
                </p>
                <ul class="mt-2 grid gap-1 text-sm text-destructive">
                    <li v-for="error in import_errors" :key="error">
                        {{ error }}
                    </li>
                </ul>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg border bg-card p-4">
                    <p class="text-sm text-muted-foreground">
                        Checked in today
                    </p>
                    <p class="mt-2 text-2xl font-semibold">
                        {{ stats.checked_in_today }}
                    </p>
                </div>
                <div class="rounded-lg border bg-card p-4">
                    <p class="text-sm text-muted-foreground">
                        Still checked in
                    </p>
                    <p class="mt-2 text-2xl font-semibold">
                        {{ stats.still_checked_in }}
                    </p>
                </div>
                <div class="rounded-lg border bg-card p-4">
                    <p class="text-sm text-muted-foreground">Late today</p>
                    <p class="mt-2 text-2xl font-semibold">
                        {{ stats.late_today }}
                    </p>
                </div>
                <div class="rounded-lg border bg-card p-4">
                    <p class="text-sm text-muted-foreground">Total records</p>
                    <p class="mt-2 text-2xl font-semibold">
                        {{ stats.total_records }}
                    </p>
                </div>
            </div>

            <div
                class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
            >
                <div class="flex flex-col gap-2 md:flex-row md:items-center">
                    <div class="relative w-full md:w-80">
                        <Search
                            class="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground"
                        />
                        <Input
                            v-model="form.search"
                            type="search"
                            placeholder="Search intern, batch, institution..."
                            class="pl-8"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                    <Input
                        v-model="form.date"
                        type="date"
                        class="w-full md:w-44"
                        @change="applyFilters"
                    />
                    <Select
                        v-model="form.status"
                        @update:model-value="applyFilters"
                    >
                        <SelectTrigger class="w-full md:w-44">
                            <SelectValue placeholder="All statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            <SelectItem
                                v-for="status in statuses"
                                :key="status.value"
                                :value="status.value"
                            >
                                {{ status.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="applyFilters">
                        Apply
                    </Button>
                    <Button
                        v-if="hasFilters"
                        variant="ghost"
                        size="icon"
                        @click="clearFilters"
                    >
                        <X class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <div
                class="overflow-hidden rounded-lg border bg-card text-card-foreground"
            >
                <Table>
                    <TableHeader class="bg-muted/40">
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="min-w-[220px] pl-4"
                                >Intern</TableHead
                            >
                            <TableHead class="min-w-[180px]">Batch</TableHead>
                            <TableHead class="w-[130px]">Date</TableHead>
                            <TableHead class="w-[120px]">Check in</TableHead>
                            <TableHead class="w-[120px]">Check out</TableHead>
                            <TableHead class="w-[120px]">Duration</TableHead>
                            <TableHead class="w-[110px]">Status</TableHead>
                            <TableHead class="min-w-[180px] pr-4"
                                >Network</TableHead
                            >
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="attendance in attendances.data"
                            :key="attendance.id"
                        >
                            <TableCell class="pl-4">
                                <div class="flex flex-col gap-1">
                                    <span class="font-medium">
                                        {{
                                            attendance.intern.name ||
                                            'Unknown intern'
                                        }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">
                                        {{
                                            attendance.intern
                                                .registration_number ||
                                            attendance.intern.email ||
                                            'No identifier'
                                        }}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-col gap-1">
                                    <span class="text-sm font-medium">
                                        {{
                                            attendance.intern.batch
                                                ?.batch_code || 'No batch'
                                        }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">
                                        {{
                                            attendance.intern.batch?.name ||
                                            attendance.intern.institution ||
                                            'Not assigned'
                                        }}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell>
                                {{ formatDate(attendance.date) }}
                            </TableCell>
                            <TableCell>
                                {{
                                    formatTime(attendance.check_in_server_time)
                                }}
                            </TableCell>
                            <TableCell>
                                {{
                                    formatTime(attendance.check_out_server_time)
                                }}
                            </TableCell>
                            <TableCell>
                                {{
                                    formatDuration(
                                        attendance.work_duration_minutes,
                                    )
                                }}
                            </TableCell>
                            <TableCell>
                                <Badge
                                    :variant="
                                        getStatusVariant(attendance.status)
                                    "
                                    class="px-2 py-0.5 text-[11px] font-medium capitalize"
                                >
                                    {{ attendance.status }}
                                </Badge>
                            </TableCell>
                            <TableCell class="pr-4">
                                <div class="flex flex-col gap-1 text-xs">
                                    <span class="font-medium">
                                        {{
                                            attendance.wifi_ssid ||
                                            'Not recorded'
                                        }}
                                    </span>
                                    <span class="text-muted-foreground">
                                        {{
                                            attendance.wifi_bssid || 'No BSSID'
                                        }}
                                    </span>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableEmpty
                            v-if="attendances.data.length === 0"
                            :colspan="8"
                        >
                            No attendance records found.
                        </TableEmpty>
                    </TableBody>
                </Table>
            </div>

            <div
                v-if="attendances.links.length > 3"
                class="flex flex-wrap items-center justify-end gap-2"
            >
                <Button
                    v-for="link in paginationLinks()"
                    :key="link.label"
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                    as-child
                    :disabled="!link.url"
                >
                    <Link v-if="link.url" :href="link.url">
                        {{ getPaginationLabel(link.label) }}
                    </Link>
                    <span v-else>{{ getPaginationLabel(link.label) }}</span>
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
