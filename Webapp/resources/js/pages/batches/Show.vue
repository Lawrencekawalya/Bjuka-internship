<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Calendar,
    Users,
    UserCheck,
    Clock,
    ShieldCheck,
    TrendingUp,
    MoreVertical,
    Wifi,
    BarChart3,
    FileText,
    Settings,
    UserPlus,
    KeyRound,
    Upload,
    Award,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
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
import {
    destroy as destroyBatch,
    edit as editBatch,
    index as batchesIndex,
    show as showBatch,
} from '@/routes/batches';
import type {
    InternshipBatch,
    BatchStats,
    BatchPerformanceAnalytics,
    BreadcrumbItem,
    Intern,
    Auth,
    AttendanceRecord,
} from '@/types';

defineOptions({
    layout: [],
});

interface Props {
    batch: InternshipBatch;
    stats: BatchStats;
    batch_attendances: AttendanceRecord[];
    batch_performance: BatchPerformanceAnalytics;
}

const props = defineProps<Props>();
const page = usePage<{ auth: Auth }>();

const activeTab = ref('overview');
const isAddInternOpen = ref(false);
const isResetPasswordOpen = ref(false);
const isCertificateOpen = ref(false);
const isReportFormatPreviewOpen = ref(false);
const selectedIntern = ref<Intern | null>(null);
const selectedCertificateIntern = ref<Intern | null>(null);
const isAdmin = computed(() => String(page.props.auth.user.role) === 'admin');
const canResetInternPassword = computed(() =>
    ['admin', 'hr'].includes(String(page.props.auth.user.role)),
);
const highestDailyAttendanceCount = computed(() =>
    Math.max(
        1,
        ...props.batch_performance.daily_attendance.map(
            (day) => day.present + day.late + day.partial + day.absent,
        ),
    ),
);

const internForm = useForm({
    name: '',
    email: '',
    phone: '',
    institution: '',
    course: '',
    registration_number: '',
    profile_photo: null as File | null,
    temporary_password: '',
    temporary_password_confirmation: '',
});

const resetPasswordForm = useForm({
    temporary_password: '',
    temporary_password_confirmation: '',
});

const certificateForm = useForm({
    certificate_file: null as File | null,
});

const reportFormatForm = useForm({
    report_format_text: props.batch.report_format_text || '',
    report_format_file: null as File | null,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
    {
        title: 'Batches',
        href: batchesIndex(),
    },
    {
        title: props.batch.batch_code,
        href: showBatch(props.batch.id),
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

const getAttendanceStatusVariant = (status: string) => {
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
    if (!value) {
        return 'Not recorded';
    }

    return new Date(value).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatTime = (value: string | null) => {
    if (!value) {
        return 'Not recorded';
    }

    return new Date(value).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatDuration = (minutes: number | null) => {
    if (minutes === null) {
        return 'In progress';
    }

    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;

    if (hours === 0) {
        return `${remainingMinutes}m`;
    }

    return `${hours}h ${remainingMinutes}m`;
};

const formatAttendanceTooltip = (
    label: string,
    count: number,
    interns: string[],
) => {
    if (count === 0) {
        return `${label}: 0 interns`;
    }

    return `${label}: ${count} ${count === 1 ? 'intern' : 'interns'}\n${interns.join('\n')}`;
};

const closeBatchUrl = (batchId: string) => `/batches/${batchId}/close`;
const batchInternsUrl = (batchId: string) => `/batches/${batchId}/interns`;
const resetInternPasswordUrl = (batchId: string, internId: string) =>
    `/batches/${batchId}/interns/${internId}/password`;
const internCertificateUrl = (batchId: string, internId: string) =>
    `/batches/${batchId}/interns/${internId}/certificate`;
const batchReportFormatUrl = (batchId: string) =>
    `/batches/${batchId}/report-format`;
const batchReportGenerationResetUrl = (batchId: string) =>
    `/batches/${batchId}/report-generation-reset`;
const reportGenerationResetUrl = (batchId: string, internId: string) =>
    `/batches/${batchId}/interns/${internId}/report-generation-reset`;

const closeBatch = () => {
    if (
        confirm('Close this batch? Interns should no longer be assigned to it.')
    ) {
        router.patch(
            closeBatchUrl(props.batch.id),
            {},
            {
                preserveScroll: true,
            },
        );
    }
};

const archiveBatch = () => {
    if (
        confirm(
            'Archive this batch? It will be hidden from active batch management.',
        )
    ) {
        router.delete(destroyBatch.url(props.batch.id));
    }
};

const addIntern = () => {
    internForm.post(batchInternsUrl(props.batch.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            isAddInternOpen.value = false;
            internForm.reset();
            internForm.clearErrors();
        },
    });
};

const openResetPasswordDialog = (intern: Intern) => {
    selectedIntern.value = intern;
    resetPasswordForm.reset();
    resetPasswordForm.clearErrors();
    isResetPasswordOpen.value = true;
};

const resetInternPassword = () => {
    if (!selectedIntern.value) {
        return;
    }

    resetPasswordForm.patch(
        resetInternPasswordUrl(props.batch.id, selectedIntern.value.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                isResetPasswordOpen.value = false;
                selectedIntern.value = null;
                resetPasswordForm.reset();
                resetPasswordForm.clearErrors();
            },
        },
    );
};

const openCertificateDialog = (intern: Intern) => {
    selectedCertificateIntern.value = intern;
    certificateForm.reset();
    certificateForm.clearErrors();
    isCertificateOpen.value = true;
};

const handleCertificateFile = (event: Event) => {
    const input = event.target as HTMLInputElement;
    certificateForm.certificate_file = input.files?.[0] ?? null;
};

const uploadCertificate = () => {
    if (!selectedCertificateIntern.value) {
        return;
    }

    certificateForm.post(
        internCertificateUrl(
            props.batch.id,
            selectedCertificateIntern.value.id,
        ),
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                isCertificateOpen.value = false;
                selectedCertificateIntern.value = null;
                certificateForm.reset();
                certificateForm.clearErrors();
            },
        },
    );
};

const handleReportFormatFile = (event: Event) => {
    const input = event.target as HTMLInputElement;
    reportFormatForm.report_format_file = input.files?.[0] ?? null;
};

const saveReportFormat = () => {
    reportFormatForm.patch(batchReportFormatUrl(props.batch.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            reportFormatForm.report_format_file = null;
        },
    });
};

const openReportFormatPreview = () => {
    isReportFormatPreviewOpen.value = true;
};

const approveReportGenerationReset = (intern: Intern) => {
    if (
        confirm(
            `Reset report generation permissions for ${intern.user?.name || 'this intern'}? This can only be done once.`,
        )
    ) {
        router.patch(reportGenerationResetUrl(props.batch.id, intern.id), {}, {
            preserveScroll: true,
        });
    }
};

const resetBatchReportGeneration = () => {
    if (
        confirm(
            'Reset report generation restrictions for all interns in this batch? This overrides used attempts, reset history, and permanent locks.',
        )
    ) {
        router.patch(batchReportGenerationResetUrl(props.batch.id), {}, {
            preserveScroll: true,
        });
    }
};

const handleProfilePhoto = (event: Event) => {
    const input = event.target as HTMLInputElement;
    internForm.profile_photo = input.files?.[0] ?? null;
};

const generateTemporaryPassword = () => {
    const password = makeTemporaryPassword();

    internForm.temporary_password = password;
    internForm.temporary_password_confirmation = password;
};

const generateResetPassword = () => {
    const password = makeTemporaryPassword();

    resetPasswordForm.temporary_password = password;
    resetPasswordForm.temporary_password_confirmation = password;
};

const makeTemporaryPassword = () => {
    const chars =
        'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
    let password = '';

    for (let index = 0; index < 12; index += 1) {
        password += chars[Math.floor(Math.random() * chars.length)];
    }

    return password;
};
</script>

<template>
    <Head :title="batch.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div
                class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
            >
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" as-child>
                        <Link :href="batchesIndex()">
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-2xl font-bold tracking-tight">
                                {{ batch.name }}
                            </h2>
                            <Badge
                                :variant="
                                    getStatusVariant(batch.virtual_status)
                                "
                            >
                                {{ batch.virtual_status.toUpperCase() }}
                            </Badge>
                        </div>
                        <p class="font-mono text-sm text-muted-foreground">
                            {{ batch.batch_code }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        v-if="isAdmin && batch.status === 'active'"
                        variant="outline"
                        @click="closeBatch"
                    >
                        Close Batch
                    </Button>
                    <Button v-if="isAdmin" variant="outline" as-child>
                        <Link :href="editBatch(batch.id)">Edit Batch</Link>
                    </Button>
                    <DropdownMenu v-if="isAdmin">
                        <DropdownMenuTrigger as-child>
                            <Button variant="outline" size="icon">
                                <MoreVertical class="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem>Generate Report</DropdownMenuItem>
                            <DropdownMenuItem>Export Interns</DropdownMenuItem>
                            <DropdownMenuItem
                                @select.prevent="resetBatchReportGeneration"
                            >
                                Reset All Report Generations
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                class="text-destructive"
                                @select.prevent="archiveBatch"
                            >
                                Archive Batch
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Total Interns</CardTitle
                        >
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stats.total_interns }} / {{ batch.capacity }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Occupancy:
                            {{
                                Math.round(
                                    (stats.total_interns / batch.capacity) *
                                        100,
                                )
                            }}%
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Present Today</CardTitle
                        >
                        <UserCheck class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stats.present_today }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Out of {{ stats.total_interns }} active
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Attendance Rate</CardTitle
                        >
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stats.attendance_rate }}%
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Historical average
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Supervisors</CardTitle
                        >
                        <ShieldCheck class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stats.total_supervisors }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Assigned mentors
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Progress</CardTitle
                        >
                        <Clock class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stats.progress }}%
                        </div>
                        <div
                            class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-secondary"
                        >
                            <div
                                class="h-full bg-primary"
                                :style="{ width: stats.progress + '%' }"
                            ></div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Custom Tabs Implementation -->
            <div class="flex flex-col gap-4">
                <div class="flex items-center border-b pb-0">
                    <button
                        v-for="tab in [
                            'overview',
                            'interns',
                            'attendance',
                            'analytics',
                            'networks',
                            'report format',
                        ]"
                        :key="tab"
                        @click="activeTab = tab"
                        :class="[
                            'relative px-4 py-2 text-sm font-medium capitalize transition-colors hover:text-primary',
                            activeTab === tab
                                ? '-mb-[2px] border-b-2 border-primary text-primary'
                                : 'text-muted-foreground',
                        ]"
                    >
                        {{ tab }}
                    </button>
                </div>

                <!-- Tab Content -->
                <div
                    v-if="activeTab === 'overview'"
                    class="grid gap-4 md:grid-cols-3"
                >
                    <Card class="md:col-span-2">
                        <CardHeader>
                            <CardTitle>Batch Description</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p
                                class="text-sm whitespace-pre-wrap text-muted-foreground"
                            >
                                {{
                                    batch.description ||
                                    'No description provided.'
                                }}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Timeline & Details</CardTitle>
                        </CardHeader>
                        <CardContent class="grid gap-4">
                            <div class="flex items-center gap-2">
                                <Calendar
                                    class="h-4 w-4 text-muted-foreground"
                                />
                                <div class="text-sm">
                                    <p class="font-medium">Duration</p>
                                    <p class="text-muted-foreground">
                                        {{ batch.start_date }} to
                                        {{ batch.end_date }}
                                    </p>
                                </div>
                            </div>
                            <Separator />
                            <div class="flex items-center gap-2">
                                <FileText
                                    class="h-4 w-4 text-muted-foreground"
                                />
                                <div class="text-sm">
                                    <p class="font-medium">Working Days</p>
                                    <p class="text-muted-foreground">
                                        {{ batch.expected_working_days }} days
                                        expected
                                    </p>
                                </div>
                            </div>
                            <Separator />
                            <div class="flex items-center gap-2">
                                <UserCheck
                                    class="h-4 w-4 text-muted-foreground"
                                />
                                <div class="text-sm">
                                    <p class="font-medium">Coordinator</p>
                                    <p class="text-muted-foreground">
                                        {{
                                            batch.coordinator?.name ||
                                            'Unassigned'
                                        }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div v-else-if="activeTab === 'interns'">
                    <Card>
                        <CardHeader>
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <div>
                                    <CardTitle>Enrolled Interns</CardTitle>
                                    <CardDescription
                                        >A total of
                                        {{ stats.total_interns }} interns are
                                        currently in this
                                        batch.</CardDescription
                                    >
                                </div>
                                <Dialog
                                    v-if="isAdmin"
                                    v-model:open="isAddInternOpen"
                                >
                                    <DialogTrigger as-child>
                                        <Button size="sm">
                                            <UserPlus class="mr-2 h-4 w-4" />
                                            Add Intern
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent class="max-w-3xl">
                                        <DialogHeader>
                                            <DialogTitle
                                                >Add Intern</DialogTitle
                                            >
                                            <DialogDescription>
                                                Create an intern account for
                                                this batch. The intern will be
                                                required to change the temporary
                                                password on first mobile login.
                                            </DialogDescription>
                                        </DialogHeader>

                                        <form
                                            class="grid gap-4 md:grid-cols-2"
                                            @submit.prevent="addIntern"
                                        >
                                            <div class="grid gap-2">
                                                <Label for="intern_name"
                                                    >Full name</Label
                                                >
                                                <Input
                                                    id="intern_name"
                                                    v-model="internForm.name"
                                                    placeholder="Jane Doe"
                                                    :class="{
                                                        'border-destructive':
                                                            internForm.errors
                                                                .name,
                                                    }"
                                                />
                                                <p
                                                    v-if="
                                                        internForm.errors.name
                                                    "
                                                    class="text-xs text-destructive"
                                                >
                                                    {{ internForm.errors.name }}
                                                </p>
                                            </div>

                                            <div class="grid gap-2">
                                                <Label for="intern_email"
                                                    >Company email</Label
                                                >
                                                <Input
                                                    id="intern_email"
                                                    v-model="internForm.email"
                                                    type="email"
                                                    placeholder="jane.doe@bjuka.com"
                                                    :class="{
                                                        'border-destructive':
                                                            internForm.errors
                                                                .email,
                                                    }"
                                                />
                                                <p
                                                    v-if="
                                                        internForm.errors.email
                                                    "
                                                    class="text-xs text-destructive"
                                                >
                                                    {{
                                                        internForm.errors.email
                                                    }}
                                                </p>
                                            </div>

                                            <div class="grid gap-2">
                                                <Label for="intern_phone"
                                                    >Phone</Label
                                                >
                                                <Input
                                                    id="intern_phone"
                                                    v-model="internForm.phone"
                                                    placeholder="+256..."
                                                />
                                                <p
                                                    v-if="
                                                        internForm.errors.phone
                                                    "
                                                    class="text-xs text-destructive"
                                                >
                                                    {{
                                                        internForm.errors.phone
                                                    }}
                                                </p>
                                            </div>

                                            <div class="grid gap-2">
                                                <Label
                                                    for="intern_registration_number"
                                                    >Registration number</Label
                                                >
                                                <Input
                                                    id="intern_registration_number"
                                                    v-model="
                                                        internForm.registration_number
                                                    "
                                                    placeholder="REG-2026-001"
                                                />
                                                <p
                                                    v-if="
                                                        internForm.errors
                                                            .registration_number
                                                    "
                                                    class="text-xs text-destructive"
                                                >
                                                    {{
                                                        internForm.errors
                                                            .registration_number
                                                    }}
                                                </p>
                                            </div>

                                            <div class="grid gap-2">
                                                <Label for="intern_institution"
                                                    >Institution</Label
                                                >
                                                <Input
                                                    id="intern_institution"
                                                    v-model="
                                                        internForm.institution
                                                    "
                                                    placeholder="University or college"
                                                />
                                                <p
                                                    v-if="
                                                        internForm.errors
                                                            .institution
                                                    "
                                                    class="text-xs text-destructive"
                                                >
                                                    {{
                                                        internForm.errors
                                                            .institution
                                                    }}
                                                </p>
                                            </div>

                                            <div class="grid gap-2">
                                                <Label for="intern_course"
                                                    >Course / program</Label
                                                >
                                                <Input
                                                    id="intern_course"
                                                    v-model="internForm.course"
                                                    placeholder="Software Engineering"
                                                />
                                                <p
                                                    v-if="
                                                        internForm.errors.course
                                                    "
                                                    class="text-xs text-destructive"
                                                >
                                                    {{
                                                        internForm.errors.course
                                                    }}
                                                </p>
                                            </div>

                                            <div
                                                class="grid gap-2 md:col-span-2"
                                            >
                                                <Label
                                                    for="intern_profile_photo"
                                                    >Profile photo</Label
                                                >
                                                <Input
                                                    id="intern_profile_photo"
                                                    type="file"
                                                    accept="image/*"
                                                    @change="handleProfilePhoto"
                                                />
                                                <p
                                                    v-if="
                                                        internForm.errors
                                                            .profile_photo
                                                    "
                                                    class="text-xs text-destructive"
                                                >
                                                    {{
                                                        internForm.errors
                                                            .profile_photo
                                                    }}
                                                </p>
                                            </div>

                                            <div class="grid gap-2">
                                                <div
                                                    class="flex items-center justify-between gap-2"
                                                >
                                                    <Label
                                                        for="intern_temporary_password"
                                                        >Temporary
                                                        password</Label
                                                    >
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        @click="
                                                            generateTemporaryPassword
                                                        "
                                                    >
                                                        Generate
                                                    </Button>
                                                </div>
                                                <Input
                                                    id="intern_temporary_password"
                                                    v-model="
                                                        internForm.temporary_password
                                                    "
                                                    type="text"
                                                    placeholder="Minimum 8 characters"
                                                    :class="{
                                                        'border-destructive':
                                                            internForm.errors
                                                                .temporary_password,
                                                    }"
                                                />
                                                <p
                                                    v-if="
                                                        internForm.errors
                                                            .temporary_password
                                                    "
                                                    class="text-xs text-destructive"
                                                >
                                                    {{
                                                        internForm.errors
                                                            .temporary_password
                                                    }}
                                                </p>
                                            </div>

                                            <div class="grid gap-2">
                                                <Label
                                                    for="intern_temporary_password_confirmation"
                                                    >Confirm temporary
                                                    password</Label
                                                >
                                                <Input
                                                    id="intern_temporary_password_confirmation"
                                                    v-model="
                                                        internForm.temporary_password_confirmation
                                                    "
                                                    type="text"
                                                    placeholder="Repeat password"
                                                />
                                            </div>

                                            <DialogFooter class="md:col-span-2">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    @click="
                                                        isAddInternOpen = false
                                                    "
                                                >
                                                    Cancel
                                                </Button>
                                                <Button
                                                    :disabled="
                                                        internForm.processing
                                                    "
                                                >
                                                    {{
                                                        internForm.processing
                                                            ? 'Adding...'
                                                            : 'Add Intern'
                                                    }}
                                                </Button>
                                            </DialogFooter>
                                        </form>
                                    </DialogContent>
                                </Dialog>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div
                                v-if="batch.interns && batch.interns.length > 0"
                                class="grid gap-3"
                            >
                                <div
                                    v-for="intern in batch.interns"
                                    :key="intern.id"
                                    class="flex items-center justify-between gap-3 rounded-lg border p-3"
                                >
                                    <div
                                        class="flex min-w-0 items-center gap-3"
                                    >
                                        <img
                                            v-if="intern.user?.avatar"
                                            :src="intern.user.avatar"
                                            :alt="intern.user.name"
                                            class="h-10 w-10 rounded-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="flex h-10 w-10 items-center justify-center rounded-full bg-secondary text-sm font-bold"
                                        >
                                            {{
                                                intern.user?.name?.slice(
                                                    0,
                                                    1,
                                                ) || 'I'
                                            }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate font-medium">
                                                {{ intern.user?.name }}
                                            </p>
                                            <p
                                                class="truncate text-xs text-muted-foreground"
                                            >
                                                {{ intern.user?.email }}
                                            </p>
                                        </div>
                                    </div>
                                    <div
                                        class="hidden text-right text-xs text-muted-foreground md:block"
                                    >
                                        <p>
                                            {{
                                                intern.institution ||
                                                'No institution'
                                            }}
                                        </p>
                                        <p>
                                            {{
                                                intern.registration_number ||
                                                'No registration number'
                                            }}
                                        </p>
                                    </div>
                                    <Badge
                                        :variant="
                                            intern.status === 'active'
                                                ? 'default'
                                                : 'secondary'
                                        "
                                    >
                                        {{ intern.status }}
                                    </Badge>
                                    <div
                                        class="flex flex-wrap justify-end gap-2"
                                    >
                                        <Badge
                                            v-if="intern.certificate_url"
                                            variant="outline"
                                        >
                                            <Award class="mr-1 h-3 w-3" />
                                            Certificate
                                        </Badge>
                                        <Badge
                                            v-if="
                                                intern.report_generation_quota
                                                    ?.reset_requested_at &&
                                                !intern.report_generation_quota
                                                    ?.reset_used
                                            "
                                            variant="secondary"
                                        >
                                            <FileText class="mr-1 h-3 w-3" />
                                            Report reset requested
                                        </Badge>
                                        <Button
                                            v-if="isAdmin"
                                            variant="outline"
                                            size="sm"
                                            @click="
                                                openCertificateDialog(intern)
                                            "
                                        >
                                            <Upload class="mr-2 h-4 w-4" />
                                            Certificate
                                        </Button>
                                        <Button
                                            v-if="
                                                isAdmin &&
                                                intern.report_generation_quota
                                                    ?.reset_requested_at &&
                                                !intern.report_generation_quota
                                                    ?.reset_used
                                            "
                                            variant="outline"
                                            size="sm"
                                            @click="
                                                approveReportGenerationReset(
                                                    intern,
                                                )
                                            "
                                        >
                                            <FileText class="mr-2 h-4 w-4" />
                                            Reset report generation
                                        </Button>
                                        <Button
                                            v-if="canResetInternPassword"
                                            variant="outline"
                                            size="sm"
                                            @click="
                                                openResetPasswordDialog(intern)
                                            "
                                        >
                                            <KeyRound class="mr-2 h-4 w-4" />
                                            Reset password
                                        </Button>
                                    </div>
                                </div>
                            </div>
                            <div
                                v-else
                                class="flex h-[200px] items-center justify-center text-muted-foreground"
                            >
                                <Users class="mr-2 h-4 w-4" />
                                No interns have been enrolled in this batch.
                            </div>
                        </CardContent>
                    </Card>

                    <Dialog v-model:open="isResetPasswordOpen">
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Reset Intern Password</DialogTitle>
                                <DialogDescription>
                                    Issue a temporary password for
                                    {{ selectedIntern?.user?.name }}. The intern
                                    must change it after logging in.
                                </DialogDescription>
                            </DialogHeader>

                            <form
                                class="grid gap-4"
                                @submit.prevent="resetInternPassword"
                            >
                                <div class="grid gap-2">
                                    <div
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <Label for="reset_temporary_password">
                                            Temporary password
                                        </Label>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            @click="generateResetPassword"
                                        >
                                            Generate
                                        </Button>
                                    </div>
                                    <Input
                                        id="reset_temporary_password"
                                        v-model="
                                            resetPasswordForm.temporary_password
                                        "
                                        type="text"
                                        placeholder="Minimum 8 characters"
                                        :class="{
                                            'border-destructive':
                                                resetPasswordForm.errors
                                                    .temporary_password,
                                        }"
                                    />
                                    <p
                                        v-if="
                                            resetPasswordForm.errors
                                                .temporary_password
                                        "
                                        class="text-xs text-destructive"
                                    >
                                        {{
                                            resetPasswordForm.errors
                                                .temporary_password
                                        }}
                                    </p>
                                </div>

                                <div class="grid gap-2">
                                    <Label
                                        for="reset_temporary_password_confirmation"
                                    >
                                        Confirm temporary password
                                    </Label>
                                    <Input
                                        id="reset_temporary_password_confirmation"
                                        v-model="
                                            resetPasswordForm.temporary_password_confirmation
                                        "
                                        type="text"
                                        placeholder="Repeat password"
                                    />
                                </div>

                                <DialogFooter>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="isResetPasswordOpen = false"
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        :disabled="resetPasswordForm.processing"
                                    >
                                        {{
                                            resetPasswordForm.processing
                                                ? 'Resetting...'
                                                : 'Reset Password'
                                        }}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>

                    <Dialog v-model:open="isCertificateOpen">
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Upload Certificate</DialogTitle>
                                <DialogDescription>
                                    Attach a PDF or image certificate for
                                    {{ selectedCertificateIntern?.user?.name }}.
                                    It becomes available in the mobile app after
                                    the internship reaches 100%.
                                </DialogDescription>
                            </DialogHeader>

                            <form
                                class="grid gap-4"
                                @submit.prevent="uploadCertificate"
                            >
                                <div class="grid gap-2">
                                    <Label for="certificate_file">
                                        Certificate file
                                    </Label>
                                    <Input
                                        id="certificate_file"
                                        type="file"
                                        accept=".pdf,image/png,image/jpeg"
                                        :class="{
                                            'border-destructive':
                                                certificateForm.errors
                                                    .certificate_file,
                                        }"
                                        @change="handleCertificateFile"
                                    />
                                    <p
                                        v-if="
                                            certificateForm.errors
                                                .certificate_file
                                        "
                                        class="text-xs text-destructive"
                                    >
                                        {{
                                            certificateForm.errors
                                                .certificate_file
                                        }}
                                    </p>
                                </div>

                                <DialogFooter>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="isCertificateOpen = false"
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        :disabled="
                                            certificateForm.processing ||
                                            !certificateForm.certificate_file
                                        "
                                    >
                                        {{
                                            certificateForm.processing
                                                ? 'Uploading...'
                                                : 'Upload Certificate'
                                        }}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <div v-else-if="activeTab === 'attendance'">
                    <Card>
                        <CardHeader>
                            <CardTitle>Attendance Log</CardTitle>
                            <CardDescription>
                                Recent attendance records for this batch.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="overflow-hidden rounded-lg border">
                                <Table>
                                    <TableHeader class="bg-muted/40">
                                        <TableRow class="hover:bg-transparent">
                                            <TableHead class="min-w-[220px]">
                                                Intern
                                            </TableHead>
                                            <TableHead class="w-[130px]">
                                                Date
                                            </TableHead>
                                            <TableHead class="w-[120px]">
                                                Check in
                                            </TableHead>
                                            <TableHead class="w-[120px]">
                                                Check out
                                            </TableHead>
                                            <TableHead class="w-[120px]">
                                                Duration
                                            </TableHead>
                                            <TableHead class="w-[110px]">
                                                Status
                                            </TableHead>
                                            <TableHead class="min-w-[160px]">
                                                Network
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow
                                            v-for="attendance in batch_attendances"
                                            :key="attendance.id"
                                        >
                                            <TableCell>
                                                <div
                                                    class="flex flex-col gap-1"
                                                >
                                                    <span class="font-medium">
                                                        {{
                                                            attendance.intern
                                                                .name ||
                                                            'Unknown intern'
                                                        }}
                                                    </span>
                                                    <span
                                                        class="text-xs text-muted-foreground"
                                                    >
                                                        {{
                                                            attendance.intern
                                                                .registration_number ||
                                                            attendance.intern
                                                                .email ||
                                                            'No identifier'
                                                        }}
                                                    </span>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {{
                                                    formatDate(attendance.date)
                                                }}
                                            </TableCell>
                                            <TableCell>
                                                {{
                                                    formatTime(
                                                        attendance.check_in_server_time,
                                                    )
                                                }}
                                            </TableCell>
                                            <TableCell>
                                                {{
                                                    formatTime(
                                                        attendance.check_out_server_time,
                                                    )
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
                                                        getAttendanceStatusVariant(
                                                            attendance.status,
                                                        )
                                                    "
                                                    class="px-2 py-0.5 text-[11px] font-medium capitalize"
                                                >
                                                    {{ attendance.status }}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div
                                                    class="flex flex-col gap-1 text-xs"
                                                >
                                                    <span class="font-medium">
                                                        {{
                                                            attendance.wifi_ssid ||
                                                            'Not recorded'
                                                        }}
                                                    </span>
                                                    <span
                                                        class="text-muted-foreground"
                                                    >
                                                        {{
                                                            attendance.wifi_bssid ||
                                                            'No BSSID'
                                                        }}
                                                    </span>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                        <TableEmpty
                                            v-if="
                                                batch_attendances.length === 0
                                            "
                                            :colspan="7"
                                        >
                                            No attendance records found for this
                                            batch.
                                        </TableEmpty>
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div v-else-if="activeTab === 'analytics'" class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-4">
                        <Card>
                            <CardHeader class="pb-2">
                                <CardTitle class="text-sm font-medium"
                                    >Expected Attendance</CardTitle
                                >
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    {{
                                        batch_performance.overview
                                            .expected_records
                                    }}
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        batch_performance.overview
                                            .elapsed_working_days
                                    }}
                                    elapsed working days
                                </p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader class="pb-2">
                                <CardTitle class="text-sm font-medium"
                                    >Recorded Attendance</CardTitle
                                >
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    {{
                                        batch_performance.overview
                                            .actual_records
                                    }}
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Non-absent intern days
                                </p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader class="pb-2">
                                <CardTitle class="text-sm font-medium"
                                    >Missing Attendance</CardTitle
                                >
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    {{
                                        batch_performance.overview
                                            .missing_records
                                    }}
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Expected days not attended
                                </p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader class="pb-2">
                                <CardTitle class="text-sm font-medium"
                                    >At Risk Interns</CardTitle
                                >
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    {{
                                        batch_performance.overview
                                            .at_risk_interns
                                    }}
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Below 75% attendance
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-3">
                        <Card class="lg:col-span-2">
                            <CardHeader>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <CardTitle
                                            >Daily Attendance Trend</CardTitle
                                        >
                                        <CardDescription>
                                            Present, late, partial, and missed
                                            attendance by working day.
                                        </CardDescription>
                                    </div>
                                    <BarChart3
                                        class="h-5 w-5 text-muted-foreground"
                                    />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div
                                    v-if="
                                        batch_performance.daily_attendance
                                            .length > 0
                                    "
                                    class="space-y-3"
                                >
                                    <div
                                        v-for="day in batch_performance.daily_attendance"
                                        :key="day.date"
                                        class="grid items-center gap-3 sm:grid-cols-[64px_1fr_48px]"
                                    >
                                        <div
                                            class="text-xs font-medium text-muted-foreground"
                                        >
                                            {{ day.label }}
                                        </div>
                                        <div
                                            class="flex h-3 overflow-hidden rounded-full bg-muted"
                                        >
                                            <div
                                                class="bg-emerald-500"
                                                :title="
                                                    formatAttendanceTooltip(
                                                        'Present',
                                                        day.present,
                                                        day.interns.present,
                                                    )
                                                "
                                                :style="{
                                                    width:
                                                        (day.present /
                                                            highestDailyAttendanceCount) *
                                                            100 +
                                                        '%',
                                                }"
                                            ></div>
                                            <div
                                                class="bg-amber-500"
                                                :title="
                                                    formatAttendanceTooltip(
                                                        'Late',
                                                        day.late,
                                                        day.interns.late,
                                                    )
                                                "
                                                :style="{
                                                    width:
                                                        (day.late /
                                                            highestDailyAttendanceCount) *
                                                            100 +
                                                        '%',
                                                }"
                                            ></div>
                                            <div
                                                class="bg-sky-500"
                                                :title="
                                                    formatAttendanceTooltip(
                                                        'Partial',
                                                        day.partial,
                                                        day.interns.partial,
                                                    )
                                                "
                                                :style="{
                                                    width:
                                                        (day.partial /
                                                            highestDailyAttendanceCount) *
                                                            100 +
                                                        '%',
                                                }"
                                            ></div>
                                            <div
                                                class="bg-rose-500"
                                                :title="
                                                    formatAttendanceTooltip(
                                                        'Missed',
                                                        day.absent,
                                                        day.interns.absent,
                                                    )
                                                "
                                                :style="{
                                                    width:
                                                        (day.absent /
                                                            highestDailyAttendanceCount) *
                                                            100 +
                                                        '%',
                                                }"
                                            ></div>
                                        </div>
                                        <div
                                            class="text-right text-xs font-medium"
                                        >
                                            {{ day.attendance_rate }}%
                                        </div>
                                    </div>
                                </div>
                                <div
                                    v-else
                                    class="flex min-h-[180px] items-center justify-center text-sm text-muted-foreground"
                                >
                                    No elapsed working days to analyze yet.
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Status Distribution</CardTitle>
                                <CardDescription>
                                    All attendance records in this batch period.
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div
                                    v-for="status in batch_performance.status_distribution"
                                    :key="status.status"
                                    class="space-y-1.5"
                                >
                                    <div
                                        class="flex items-center justify-between text-sm"
                                    >
                                        <span class="capitalize">{{
                                            status.status
                                        }}</span>
                                        <span class="text-muted-foreground">
                                            {{ status.count }} ({{
                                                status.percentage
                                            }}%)
                                        </span>
                                    </div>
                                    <div
                                        class="h-2 overflow-hidden rounded-full bg-muted"
                                    >
                                        <div
                                            class="h-full bg-primary"
                                            :style="{
                                                width:
                                                    status.percentage + '%',
                                            }"
                                        ></div>
                                    </div>
                                </div>
                                <Separator />
                                <div
                                    class="flex items-center justify-between text-sm"
                                >
                                    <span>Average hours per attendance</span>
                                    <span class="font-medium">
                                        {{
                                            batch_performance.overview
                                                .average_hours_per_attendance
                                        }}h
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Intern Performance</CardTitle>
                            <CardDescription>
                                Ranked from lowest to highest attendance rate.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Intern</TableHead>
                                            <TableHead>Rate</TableHead>
                                            <TableHead>Attended</TableHead>
                                            <TableHead>Missed</TableHead>
                                            <TableHead>Total Hours</TableHead>
                                            <TableHead>Last Attended</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow
                                            v-for="intern in batch_performance.intern_performance"
                                            :key="intern.id"
                                        >
                                            <TableCell>
                                                <div class="font-medium">
                                                    {{ intern.name }}
                                                </div>
                                                <div
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    {{ intern.email || '—' }}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    :variant="
                                                        intern.attendance_rate <
                                                        75
                                                            ? 'destructive'
                                                            : 'default'
                                                    "
                                                >
                                                    {{
                                                        intern.attendance_rate
                                                    }}%
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {{ intern.attended_days }}
                                            </TableCell>
                                            <TableCell>
                                                {{ intern.missed_days }}
                                            </TableCell>
                                            <TableCell>
                                                {{ intern.total_hours }}h
                                            </TableCell>
                                            <TableCell>
                                                {{
                                                    formatDate(
                                                        intern.last_attended_on,
                                                    )
                                                }}
                                            </TableCell>
                                        </TableRow>
                                        <TableEmpty
                                            v-if="
                                                batch_performance
                                                    .intern_performance
                                                    .length === 0
                                            "
                                            :colspan="6"
                                        >
                                            No active interns are available for
                                            analytics.
                                        </TableEmpty>
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div v-else-if="activeTab === 'networks'">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle>Approved WiFi Networks</CardTitle>
                                <Button size="sm">
                                    <Wifi class="mr-2 h-4 w-4" />
                                    Add Network
                                </Button>
                            </div>
                            <CardDescription
                                >Only attendance from these networks will be
                                accepted.</CardDescription
                            >
                        </CardHeader>
                        <CardContent>
                            <div
                                v-if="
                                    batch.approved_networks &&
                                    batch.approved_networks.length > 0
                                "
                                class="grid gap-4"
                            >
                                <div
                                    v-for="network in batch.approved_networks"
                                    :key="network.id"
                                    class="flex items-center justify-between rounded-lg border p-3"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="rounded-full bg-secondary p-2"
                                        >
                                            <Wifi class="h-4 w-4" />
                                        </div>
                                        <div>
                                            <p class="font-medium">
                                                {{ network.name }}
                                            </p>
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                SSID: {{ network.ssid }} |
                                                BSSID: {{ network.bssid }}
                                            </p>
                                        </div>
                                    </div>
                                    <Button variant="ghost" size="icon">
                                        <Settings class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                            <div
                                v-else
                                class="flex h-[200px] flex-col items-center justify-center text-muted-foreground"
                            >
                                <Wifi class="mb-2 h-8 w-8 opacity-20" />
                                <p>
                                    No approved networks configured for this
                                    batch.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div v-else-if="activeTab === 'report format'">
                    <Card>
                        <CardHeader>
                            <CardTitle>Intern Report Format</CardTitle>
                            <CardDescription>
                                This format is used when interns in this batch
                                generate their final report draft.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form
                                class="grid gap-5"
                                @submit.prevent="saveReportFormat"
                            >
                                <div class="grid gap-2">
                                    <Label for="report_format_text">
                                        Report format text
                                    </Label>
                                    <textarea
                                        id="report_format_text"
                                        v-model="
                                            reportFormatForm.report_format_text
                                        "
                                        class="min-h-[320px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                        placeholder="Paste the report structure here. Example: Cover Page, Declaration, Chapter One..."
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        Paste the required headings,
                                        instructions, chapter order, and any
                                        formatting notes. The uploaded file is
                                        stored for reference only.
                                    </p>
                                    <p
                                        v-if="
                                            reportFormatForm.errors
                                                .report_format_text
                                        "
                                        class="text-sm text-destructive"
                                    >
                                        {{
                                            reportFormatForm.errors
                                                .report_format_text
                                        }}
                                    </p>
                                </div>

                                <div class="grid gap-2">
                                    <Label for="report_format_file">
                                        Reference format file
                                    </Label>
                                    <Input
                                        id="report_format_file"
                                        type="file"
                                        accept=".txt,.doc,.docx,.odt,.pdf"
                                        @change="handleReportFormatFile"
                                    />
                                    <p
                                        v-if="
                                            batch.report_format_original_name
                                        "
                                        class="text-xs text-muted-foreground"
                                    >
                                        Current file:
                                        {{ batch.report_format_original_name }}
                                    </p>
                                    <p
                                        v-if="
                                            reportFormatForm.errors
                                                .report_format_file
                                        "
                                        class="text-sm text-destructive"
                                    >
                                        {{
                                            reportFormatForm.errors
                                                .report_format_file
                                        }}
                                    </p>
                                </div>

                                <div class="flex flex-wrap justify-end gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="openReportFormatPreview"
                                    >
                                        <FileText class="mr-2 h-4 w-4" />
                                        Preview Format
                                    </Button>
                                    <Button
                                        type="submit"
                                        :disabled="reportFormatForm.processing"
                                    >
                                        {{
                                            reportFormatForm.processing
                                                ? 'Saving...'
                                                : 'Save Report Format'
                                        }}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Dialog v-model:open="isReportFormatPreviewOpen">
                        <DialogContent class="max-w-3xl">
                            <DialogHeader>
                                <DialogTitle>
                                    Report Format Preview
                                </DialogTitle>
                                <DialogDescription>
                                    This is the format text the AI will follow
                                    for interns in this batch.
                                </DialogDescription>
                            </DialogHeader>

                            <div
                                class="max-h-[65vh] overflow-y-auto rounded-lg border bg-muted/30 p-4"
                            >
                                <pre
                                    v-if="
                                        reportFormatForm.report_format_text.trim()
                                    "
                                    class="whitespace-pre-wrap break-words font-sans text-sm leading-6"
                                >
{{ reportFormatForm.report_format_text }}</pre
                                >
                                <div
                                    v-else
                                    class="flex min-h-[180px] items-center justify-center text-sm text-muted-foreground"
                                >
                                    No report format text has been entered.
                                </div>
                            </div>

                            <DialogFooter>
                                <Button
                                    type="button"
                                    @click="isReportFormatPreviewOpen = false"
                                >
                                    Close
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
