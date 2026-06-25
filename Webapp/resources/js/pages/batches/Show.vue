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
    BreadcrumbItem,
    Intern,
    Auth,
} from '@/types';

defineOptions({
    layout: [],
});

interface Props {
    batch: InternshipBatch;
    stats: BatchStats;
}

const props = defineProps<Props>();
const page = usePage<{ auth: Auth }>();

const activeTab = ref('overview');
const isAddInternOpen = ref(false);
const isResetPasswordOpen = ref(false);
const selectedIntern = ref<Intern | null>(null);
const isAdmin = computed(() => String(page.props.auth.user.role) === 'admin');
const canResetInternPassword = computed(() =>
    ['admin', 'hr'].includes(String(page.props.auth.user.role)),
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

const closeBatchUrl = (batchId: string) => `/batches/${batchId}/close`;
const batchInternsUrl = (batchId: string) => `/batches/${batchId}/interns`;
const resetInternPasswordUrl = (batchId: string, internId: string) =>
    `/batches/${batchId}/interns/${internId}/password`;

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
                                    <Button
                                        v-if="canResetInternPassword"
                                        variant="outline"
                                        size="sm"
                                        @click="openResetPasswordDialog(intern)"
                                    >
                                        <KeyRound class="mr-2 h-4 w-4" />
                                        Reset password
                                    </Button>
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
                </div>

                <div v-else-if="activeTab === 'attendance'">
                    <Card>
                        <CardHeader>
                            <CardTitle>Attendance Log</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div
                                class="flex h-[200px] items-center justify-center text-muted-foreground"
                            >
                                <Clock class="mr-2 h-4 w-4" />
                                Attendance dashboard will be integrated here.
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div v-else-if="activeTab === 'analytics'">
                    <Card>
                        <CardHeader>
                            <CardTitle>Batch Performance Analytics</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div
                                class="flex h-[200px] items-center justify-center text-muted-foreground"
                            >
                                <BarChart3 class="mr-2 h-4 w-4" />
                                Analytics charts will be integrated here.
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
            </div>
        </div>
    </AppLayout>
</template>
