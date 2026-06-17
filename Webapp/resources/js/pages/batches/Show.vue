<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
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
    Settings
} from '@lucide/vue';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import {
    destroy as destroyBatch,
    edit as editBatch,
    index as batchesIndex,
    show as showBatch,
} from '@/routes/batches';
import type { InternshipBatch, BatchStats, BreadcrumbItem } from '@/types';

defineOptions({
    layout: [],
});

interface Props {
    batch: InternshipBatch;
    stats: BatchStats;
}

const props = defineProps<Props>();

const activeTab = ref('overview');

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
        case 'active': return 'default';
        case 'upcoming': return 'secondary';
        case 'closed': return 'destructive';
        case 'archived': return 'outline';
        default: return 'default';
    }
};

const closeBatchUrl = (batchId: string) => `/batches/${batchId}/close`;

const closeBatch = () => {
    if (confirm('Close this batch? Interns should no longer be assigned to it.')) {
        router.patch(closeBatchUrl(props.batch.id), {}, {
            preserveScroll: true,
        });
    }
};

const archiveBatch = () => {
    if (confirm('Archive this batch? It will be hidden from active batch management.')) {
        router.delete(destroyBatch.url(props.batch.id));
    }
};
</script>

<template>
    <Head :title="batch.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" as-child>
                        <Link :href="batchesIndex()">
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-2xl font-bold tracking-tight">{{ batch.name }}</h2>
                            <Badge :variant="getStatusVariant(batch.virtual_status)">
                                {{ batch.virtual_status.toUpperCase() }}
                            </Badge>
                        </div>
                        <p class="text-sm text-muted-foreground font-mono">{{ batch.batch_code }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        v-if="batch.status === 'active'"
                        variant="outline"
                        @click="closeBatch"
                    >
                        Close Batch
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="editBatch(batch.id)">Edit Batch</Link>
                    </Button>
                    <DropdownMenu>
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
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Interns</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_interns }} / {{ batch.capacity }}</div>
                        <p class="text-xs text-muted-foreground">Occupancy: {{ Math.round((stats.total_interns / batch.capacity) * 100) }}%</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Present Today</CardTitle>
                        <UserCheck class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.present_today }}</div>
                        <p class="text-xs text-muted-foreground">Out of {{ stats.total_interns }} active</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Attendance Rate</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.attendance_rate }}%</div>
                        <p class="text-xs text-muted-foreground">Historical average</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Supervisors</CardTitle>
                        <ShieldCheck class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_supervisors }}</div>
                        <p class="text-xs text-muted-foreground">Assigned mentors</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Progress</CardTitle>
                        <Clock class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.progress }}%</div>
                        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-secondary">
                            <div class="h-full bg-primary" :style="{ width: stats.progress + '%' }"></div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Custom Tabs Implementation -->
            <div class="flex flex-col gap-4">
                <div class="flex items-center border-b pb-0">
                    <button 
                        v-for="tab in ['overview', 'interns', 'attendance', 'analytics', 'networks']" 
                        :key="tab"
                        @click="activeTab = tab"
                        :class="[
                            'px-4 py-2 text-sm font-medium transition-colors hover:text-primary relative capitalize',
                            activeTab === tab ? 'text-primary border-b-2 border-primary -mb-[2px]' : 'text-muted-foreground'
                        ]"
                    >
                        {{ tab }}
                    </button>
                </div>

                <!-- Tab Content -->
                <div v-if="activeTab === 'overview'" class="grid gap-4 md:grid-cols-3">
                    <Card class="md:col-span-2">
                        <CardHeader>
                            <CardTitle>Batch Description</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-muted-foreground whitespace-pre-wrap">{{ batch.description || 'No description provided.' }}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Timeline & Details</CardTitle>
                        </CardHeader>
                        <CardContent class="grid gap-4">
                            <div class="flex items-center gap-2">
                                <Calendar class="h-4 w-4 text-muted-foreground" />
                                <div class="text-sm">
                                    <p class="font-medium">Duration</p>
                                    <p class="text-muted-foreground">{{ batch.start_date }} to {{ batch.end_date }}</p>
                                </div>
                            </div>
                            <Separator />
                            <div class="flex items-center gap-2">
                                <FileText class="h-4 w-4 text-muted-foreground" />
                                <div class="text-sm">
                                    <p class="font-medium">Working Days</p>
                                    <p class="text-muted-foreground">{{ batch.expected_working_days }} days expected</p>
                                </div>
                            </div>
                            <Separator />
                            <div class="flex items-center gap-2">
                                <UserCheck class="h-4 w-4 text-muted-foreground" />
                                <div class="text-sm">
                                    <p class="font-medium">Coordinator</p>
                                    <p class="text-muted-foreground">{{ batch.coordinator?.name || 'Unassigned' }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div v-else-if="activeTab === 'interns'">
                    <Card>
                        <CardHeader>
                            <CardTitle>Enrolled Interns</CardTitle>
                            <CardDescription>A total of {{ stats.total_interns }} interns are currently in this batch.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="flex h-[200px] items-center justify-center text-muted-foreground">
                                <Users class="mr-2 h-4 w-4" />
                                Intern list component will be integrated here.
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div v-else-if="activeTab === 'attendance'">
                    <Card>
                        <CardHeader>
                            <CardTitle>Attendance Log</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex h-[200px] items-center justify-center text-muted-foreground">
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
                            <div class="flex h-[200px] items-center justify-center text-muted-foreground">
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
                            <CardDescription>Only attendance from these networks will be accepted.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="batch.approved_networks && batch.approved_networks.length > 0" class="grid gap-4">
                                <div v-for="network in batch.approved_networks" :key="network.id" class="flex items-center justify-between rounded-lg border p-3">
                                    <div class="flex items-center gap-3">
                                        <div class="rounded-full bg-secondary p-2">
                                            <Wifi class="h-4 w-4" />
                                        </div>
                                        <div>
                                            <p class="font-medium">{{ network.name }}</p>
                                            <p class="text-xs text-muted-foreground">SSID: {{ network.ssid }} | BSSID: {{ network.bssid }}</p>
                                        </div>
                                    </div>
                                    <Button variant="ghost" size="icon">
                                        <Settings class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                            <div v-else class="flex h-[200px] flex-col items-center justify-center text-muted-foreground">
                                <Wifi class="mb-2 h-8 w-8 opacity-20" />
                                <p>No approved networks configured for this batch.</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
