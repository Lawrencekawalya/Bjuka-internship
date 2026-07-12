<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    BookOpen,
    Bot,
    CalendarCheck,
    ClipboardCheck,
    FileText,
    GraduationCap,
    ShieldCheck,
    Smartphone,
    Users,
    Wifi,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type DocSection = {
    id: string;
    title: string;
    description: string;
    icon: unknown;
};

type GuideBlock = {
    title: string;
    points: string[];
};

defineOptions({
    layout: [],
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
    {
        title: 'Documentation',
        href: '/documentation',
    },
];

const sections: DocSection[] = [
    {
        id: 'overview',
        title: 'Overview',
        description: 'System purpose, apps, and core workflow.',
        icon: BookOpen,
    },
    {
        id: 'roles',
        title: 'Roles',
        description: 'Who can access each part of the system.',
        icon: ShieldCheck,
    },
    {
        id: 'admin',
        title: 'Admin Guide',
        description: 'Setup and operate internship batches.',
        icon: Users,
    },
    {
        id: 'supervisor',
        title: 'Supervisor Guide',
        description: 'Monitor attendance and support interns.',
        icon: ClipboardCheck,
    },
    {
        id: 'intern',
        title: 'Intern Guide',
        description: 'Mobile attendance, reports, and certificates.',
        icon: Smartphone,
    },
    {
        id: 'reports',
        title: 'AI Reports',
        description: 'Report generation rules and review workflow.',
        icon: Bot,
    },
    {
        id: 'troubleshooting',
        title: 'Troubleshooting',
        description: 'Common problems and practical fixes.',
        icon: FileText,
    },
];

const activeSection = ref(sections[0].id);

const activeSectionDetails = computed(
    () => sections.find((section) => section.id === activeSection.value) ?? sections[0],
);

const adminBlocks: GuideBlock[] = [
    {
        title: 'Before Starting A Batch',
        points: [
            'Create the internship batch with the correct start date, end date, capacity, and expected working days.',
            'Confirm the active batch is the one that should appear as the system dashboard for admin and HR users.',
            'Add approved WiFi networks under the batch Networks tab so mobile attendance is accepted only from trusted networks.',
            'Review the Intern Program tab and update weekly objectives, topics, activities, and projects for the actual training plan.',
            'Add the report format in the Report Format tab before interns reach completion.',
        ],
    },
    {
        title: 'Managing Interns',
        points: [
            'Add interns from the batch Interns tab so each intern is linked to the correct batch.',
            'Use Reset Password when an intern cannot access the mobile app.',
            'Assign supervisors from the Interns tab so supervisor counts and supervision records stay accurate.',
            'Upload certificates for completed interns from the batch Interns tab.',
            'Use Reset All Report Generations only when the batch needs a full administrative override.',
        ],
    },
    {
        title: 'Closing A Batch',
        points: [
            'Close the batch when attendance collection should stop.',
            'After closure, mobile check-in and check-out are blocked, but interns can still view history, program content, certificates, and eligible final reports.',
            'Archive only when the batch should no longer be treated as active operational work.',
        ],
    },
];

const supervisorBlocks: GuideBlock[] = [
    {
        title: 'Attendance Monitoring',
        points: [
            'Use the Attendance page to review check-in, check-out, duration, WiFi, and attendance status records.',
            'Use the batch Analytics tab to identify low attendance rates, missed days, late attendance, and partial attendance patterns.',
            'The Intern Performance table is ranked from lowest to highest attendance rate so at-risk interns appear first.',
        ],
    },
    {
        title: 'Supporting Interns',
        points: [
            'Follow up with interns who have missed days or repeated partial attendance.',
            'Confirm that interns are checking out with useful learning logs because those logs are used in final report drafting.',
            'Tell admins when an intern needs certificate upload, password reset, or report-generation permission reset.',
        ],
    },
];

const internBlocks: GuideBlock[] = [
    {
        title: 'Mobile App Access',
        points: [
            'Only intern accounts can log in to the mobile app.',
            'On first login or after a password reset, interns may be required to set a new password.',
            'If login fails, the intern should confirm their email and password with the administrator.',
        ],
    },
    {
        title: 'Attendance',
        points: [
            'Interns check in and check out from the mobile app while connected to an approved batch WiFi network.',
            'At check-out, interns should write clear learning logs describing tasks completed, skills learned, challenges, and practical work.',
            'When a batch is closed or archived, attendance is no longer available, but history and completion resources remain accessible.',
        ],
    },
    {
        title: 'Completion Resources',
        points: [
            'The certificate button appears when the internship is complete and the admin has uploaded a certificate.',
            'The report generator appears only after internship completion.',
            'Generated Word reports are drafts. Interns must review, add real images, format, and submit according to their institution requirements.',
        ],
    },
];

const troubleshootingBlocks: GuideBlock[] = [
    {
        title: 'Intern Cannot Check In Or Out',
        points: [
            'Confirm the intern is assigned to an active batch.',
            'Confirm the batch is not closed or archived.',
            'Confirm the phone is connected to one of the approved WiFi networks for that batch.',
            'Check that the intern account status is active.',
        ],
    },
    {
        title: 'Report Generation Failed',
        points: [
            'Confirm the OpenAI API key is configured on the backend server.',
            'Confirm the batch has a report format entered or uploaded.',
            'Review backend logs for the exact OpenAI or document-generation error.',
            'If the intern used all attempts, the admin can approve a reset or use the batch-wide reset override.',
        ],
    },
    {
        title: 'Attendance Rate Looks Wrong',
        points: [
            'Attendance rates are calculated against expected working days, not only days where records exist.',
            'Missed days increase when an active intern has no attendance record for an expected working day.',
            'Closed or archived batch status stops new attendance but does not rewrite past attendance.',
        ],
    },
];
</script>

<template>
    <Head title="System Documentation" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div
                class="flex flex-col gap-4 border-b pb-5 md:flex-row md:items-end md:justify-between"
            >
                <div class="max-w-3xl">
                    <div class="mb-2 flex items-center gap-2">
                        <Badge variant="outline">BJUKA Internship</Badge>
                        <Badge variant="secondary">Operations Manual</Badge>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                        System Documentation
                    </h1>
                    <p class="mt-2 text-sm leading-6 text-muted-foreground">
                        Practical guidance for administrators, supervisors, and
                        interns using the internship attendance, program,
                        certificate, and report-generation system.
                    </p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
                <aside class="lg:sticky lg:top-4 lg:self-start">
                    <div class="rounded-lg border bg-card p-2">
                        <button
                            v-for="section in sections"
                            :key="section.id"
                            type="button"
                            :class="[
                                'flex w-full items-start gap-3 rounded-md px-3 py-3 text-left transition-colors',
                                activeSection === section.id
                                    ? 'bg-secondary text-foreground'
                                    : 'text-muted-foreground hover:bg-secondary/60 hover:text-foreground',
                            ]"
                            @click="activeSection = section.id"
                        >
                            <component
                                :is="section.icon"
                                class="mt-0.5 h-4 w-4 shrink-0"
                            />
                            <span>
                                <span class="block text-sm font-medium">
                                    {{ section.title }}
                                </span>
                                <span class="block text-xs leading-5">
                                    {{ section.description }}
                                </span>
                            </span>
                        </button>
                    </div>
                </aside>

                <main class="min-w-0">
                    <section class="rounded-lg border bg-card p-5 md:p-6">
                        <div class="mb-5 flex items-start gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-md bg-secondary"
                            >
                                <component
                                    :is="activeSectionDetails.icon"
                                    class="h-5 w-5"
                                />
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold">
                                    {{ activeSectionDetails.title }}
                                </h2>
                                <p class="text-sm text-muted-foreground">
                                    {{ activeSectionDetails.description }}
                                </p>
                            </div>
                        </div>

                        <div v-if="activeSection === 'overview'" class="space-y-6">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="rounded-lg border p-4">
                                    <h3 class="font-semibold">Web App</h3>
                                    <p
                                        class="mt-2 text-sm leading-6 text-muted-foreground"
                                    >
                                        Used by administrators, HR,
                                        supervisors, managers, and center
                                        directors to manage batches, interns,
                                        attendance, networks, certificates,
                                        reports, and documentation.
                                    </p>
                                </div>
                                <div class="rounded-lg border p-4">
                                    <h3 class="font-semibold">Mobile App</h3>
                                    <p
                                        class="mt-2 text-sm leading-6 text-muted-foreground"
                                    >
                                        Used by interns for login, attendance
                                        check-in and check-out, learning logs,
                                        history, intern program viewing,
                                        certificates, and final report drafts.
                                    </p>
                                </div>
                            </div>

                            <Separator />

                            <div>
                                <h3 class="mb-3 font-semibold">
                                    Standard Internship Workflow
                                </h3>
                                <div class="grid gap-3 md:grid-cols-5">
                                    <div class="rounded-lg border p-3">
                                        <CalendarCheck class="mb-2 h-4 w-4" />
                                        <p class="text-sm font-medium">
                                            Create Batch
                                        </p>
                                    </div>
                                    <div class="rounded-lg border p-3">
                                        <Wifi class="mb-2 h-4 w-4" />
                                        <p class="text-sm font-medium">
                                            Approve WiFi
                                        </p>
                                    </div>
                                    <div class="rounded-lg border p-3">
                                        <GraduationCap class="mb-2 h-4 w-4" />
                                        <p class="text-sm font-medium">
                                            Add Interns
                                        </p>
                                    </div>
                                    <div class="rounded-lg border p-3">
                                        <ClipboardCheck class="mb-2 h-4 w-4" />
                                        <p class="text-sm font-medium">
                                            Track Attendance
                                        </p>
                                    </div>
                                    <div class="rounded-lg border p-3">
                                        <FileText class="mb-2 h-4 w-4" />
                                        <p class="text-sm font-medium">
                                            Complete Reports
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="activeSection === 'roles'" class="space-y-4">
                            <div class="overflow-hidden rounded-lg border">
                                <table class="w-full text-sm">
                                    <thead class="bg-muted/40 text-left">
                                        <tr>
                                            <th class="px-4 py-3">Role</th>
                                            <th class="px-4 py-3">Primary Access</th>
                                            <th class="px-4 py-3">Main Responsibility</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <tr>
                                            <td class="px-4 py-3 font-medium">Admin</td>
                                            <td class="px-4 py-3">Full web management</td>
                                            <td class="px-4 py-3">Configure batches, users, interns, reports, networks, and certificates.</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 font-medium">HR</td>
                                            <td class="px-4 py-3">Batch and attendance visibility</td>
                                            <td class="px-4 py-3">Follow batch progress and support internship operations.</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 font-medium">Supervisor</td>
                                            <td class="px-4 py-3">Attendance monitoring</td>
                                            <td class="px-4 py-3">Monitor assigned interns and follow up on attendance or learning issues.</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 font-medium">Manager</td>
                                            <td class="px-4 py-3">Attendance visibility</td>
                                            <td class="px-4 py-3">Review attendance and performance trends.</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 font-medium">Center Director</td>
                                            <td class="px-4 py-3">Attendance visibility</td>
                                            <td class="px-4 py-3">Oversee center-level internship performance.</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 font-medium">Intern</td>
                                            <td class="px-4 py-3">Mobile app only</td>
                                            <td class="px-4 py-3">Record attendance, submit learning logs, view program, and prepare final report.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div v-else-if="activeSection === 'admin'" class="space-y-4">
                            <div
                                v-for="block in adminBlocks"
                                :key="block.title"
                                class="rounded-lg border p-4"
                            >
                                <h3 class="font-semibold">{{ block.title }}</h3>
                                <ul
                                    class="mt-3 list-disc space-y-2 pl-5 text-sm leading-6 text-muted-foreground"
                                >
                                    <li
                                        v-for="point in block.points"
                                        :key="point"
                                    >
                                        {{ point }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div
                            v-else-if="activeSection === 'supervisor'"
                            class="space-y-4"
                        >
                            <div
                                v-for="block in supervisorBlocks"
                                :key="block.title"
                                class="rounded-lg border p-4"
                            >
                                <h3 class="font-semibold">{{ block.title }}</h3>
                                <ul
                                    class="mt-3 list-disc space-y-2 pl-5 text-sm leading-6 text-muted-foreground"
                                >
                                    <li
                                        v-for="point in block.points"
                                        :key="point"
                                    >
                                        {{ point }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div v-else-if="activeSection === 'intern'" class="space-y-4">
                            <div
                                v-for="block in internBlocks"
                                :key="block.title"
                                class="rounded-lg border p-4"
                            >
                                <h3 class="font-semibold">{{ block.title }}</h3>
                                <ul
                                    class="mt-3 list-disc space-y-2 pl-5 text-sm leading-6 text-muted-foreground"
                                >
                                    <li
                                        v-for="point in block.points"
                                        :key="point"
                                    >
                                        {{ point }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div v-else-if="activeSection === 'reports'" class="space-y-4">
                            <div class="rounded-lg border p-4">
                                <h3 class="font-semibold">
                                    Intern Report Draft Flow
                                </h3>
                                <div
                                    class="mt-3 flex flex-wrap items-center gap-2 text-sm"
                                >
                                    <Badge>Generate Internship Report</Badge>
                                    <span class="text-muted-foreground">to</span>
                                    <Badge variant="secondary">Preview Draft</Badge>
                                    <span class="text-muted-foreground">to</span>
                                    <Badge variant="outline">Download Word Document</Badge>
                                </div>
                                <p
                                    class="mt-4 text-sm leading-6 text-muted-foreground"
                                >
                                    The generator becomes available only after
                                    internship completion. The AI uses the
                                    batch report format, intern learning logs,
                                    attendance records, and stored intern
                                    program weeks to create a structured draft.
                                </p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="rounded-lg border p-4">
                                    <h3 class="font-semibold">Usage Limits</h3>
                                    <p
                                        class="mt-2 text-sm leading-6 text-muted-foreground"
                                    >
                                        Each intern receives 3 report-generation
                                        attempts. After the first limit is
                                        reached, the intern can request one
                                        admin reset. The admin can also reset all
                                        interns in a batch back to default.
                                    </p>
                                </div>
                                <div class="rounded-lg border p-4">
                                    <h3 class="font-semibold">
                                        Image Placeholders
                                    </h3>
                                    <p
                                        class="mt-2 text-sm leading-6 text-muted-foreground"
                                    >
                                        Generated reports include placeholder
                                        notes where interns should insert real
                                        photos from practical training before
                                        final submission.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else-if="activeSection === 'troubleshooting'"
                            class="space-y-4"
                        >
                            <div
                                v-for="block in troubleshootingBlocks"
                                :key="block.title"
                                class="rounded-lg border p-4"
                            >
                                <h3 class="font-semibold">{{ block.title }}</h3>
                                <ul
                                    class="mt-3 list-disc space-y-2 pl-5 text-sm leading-6 text-muted-foreground"
                                >
                                    <li
                                        v-for="point in block.points"
                                        :key="point"
                                    >
                                        {{ point }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </section>

                    <div
                        class="mt-4 flex flex-wrap justify-end gap-2 border-t pt-4"
                    >
                        <Button
                            v-for="section in sections"
                            :key="section.id"
                            type="button"
                            size="sm"
                            :variant="
                                activeSection === section.id
                                    ? 'default'
                                    : 'outline'
                            "
                            @click="activeSection = section.id"
                        >
                            {{ section.title }}
                        </Button>
                    </div>
                </main>
            </div>
        </div>
    </AppLayout>
</template>
