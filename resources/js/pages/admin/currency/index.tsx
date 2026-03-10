import { useForm, usePage } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import { CheckCircle, RefreshCw, TrendingUp, XCircle } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import {
    index as adminCurrenciesRoute,
    refresh as adminCurrenciesRefreshRoute,
} from '@/routes/admin/currencies';
import type { BreadcrumbItem } from '@/types';

// ─── Types ───────────────────────────────────────────────────────────────────

interface CurrencyRow {
    id: number;
    code: string;
    name: string;
    /** Rate relative to USD (base). null when no rate has been fetched yet. */
    rate: string | null;
    fetched_at: string | null;
}

interface Props {
    currencies: CurrencyRow[];
}

// ─── Breadcrumbs ─────────────────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '#' },
    { title: 'Currency Rates', href: adminCurrenciesRoute() },
];

// ─── Helpers ─────────────────────────────────────────────────────────────────

function formatRate(rate: string | null): string {
    if (rate === null) return '—';
    return parseFloat(rate).toFixed(6);
}

function formatDate(isoString: string | null): string {
    if (isoString === null) return '—';
    return new Intl.DateTimeFormat('en-US', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(isoString));
}

// ─── Component ───────────────────────────────────────────────────────────────

export default function CurrencyIndex({ currencies }: Props) {
    const { flash } = usePage().props;

    // useForm with no data — we only need the submit state (processing)
    const { post, processing } = useForm({});

    function handleRefresh(e: React.FormEvent) {
        e.preventDefault();
        post(adminCurrenciesRefreshRoute().url);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Currency Rates" />

            <div className="flex flex-col gap-6 p-6">
                {/* Flash messages */}
                {flash.success && (
                    <div className="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-300">
                        <CheckCircle className="size-4 shrink-0" />
                        {flash.success}
                    </div>
                )}
                {flash.error && (
                    <div className="flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-300">
                        <XCircle className="size-4 shrink-0" />
                        {flash.error}
                    </div>
                )}

                {/* Page header */}
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <div className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <TrendingUp className="size-5" />
                        </div>
                        <div>
                            <h1 className="text-xl font-semibold text-foreground">
                                Exchange Rates
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                All rates are relative to{' '}
                                <span className="font-medium text-foreground">
                                    USD
                                </span>{' '}
                                (base currency). Updated daily via freecurrencyapi.com.
                            </p>
                        </div>
                    </div>

                    {/* Refresh button — submits a POST to trigger the Artisan command */}
                    <form onSubmit={handleRefresh}>
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <RefreshCw
                                className={`size-4 ${processing ? 'animate-spin' : ''}`}
                            />
                            {processing ? 'Refreshing…' : 'Refresh Rates'}
                        </button>
                    </form>
                </div>

                {/* Rates table */}
                <div className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                    {currencies.length === 0 ? (
                        <div className="flex flex-col items-center gap-3 py-16 text-center">
                            <TrendingUp className="size-10 text-muted-foreground/40" />
                            <p className="text-sm text-muted-foreground">
                                No currency rates found. Click{' '}
                                <span className="font-medium text-foreground">
                                    Refresh Rates
                                </span>{' '}
                                above or run{' '}
                                <code className="rounded bg-muted px-1.5 py-0.5 text-xs font-mono">
                                    make fetch-rates
                                </code>{' '}
                                to fetch the latest rates.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-sidebar-border/70 bg-muted/40 dark:border-sidebar-border">
                                        <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                            Code
                                        </th>
                                        <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                            Currency Name
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium text-muted-foreground">
                                            Rate (vs USD)
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium text-muted-foreground">
                                            Last Updated
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-sidebar-border/50 dark:divide-sidebar-border">
                                    {currencies.map((currency) => (
                                        <tr
                                            key={currency.id}
                                            className="transition-colors hover:bg-muted/30"
                                        >
                                            <td className="px-4 py-3">
                                                <span className="inline-flex items-center rounded-md bg-primary/10 px-2.5 py-0.5 font-mono text-xs font-semibold text-primary">
                                                    {currency.code}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-foreground">
                                                {currency.name}
                                            </td>
                                            <td className="px-4 py-3 text-right font-mono text-foreground">
                                                {formatRate(currency.rate)}
                                            </td>
                                            <td className="px-4 py-3 text-right text-muted-foreground">
                                                {formatDate(currency.fetched_at)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {/* Footer note */}
                <p className="text-xs text-muted-foreground">
                    Rates are fetched once per day automatically. To refresh
                    manually via CLI, run{' '}
                    <code className="rounded bg-muted px-1.5 py-0.5 font-mono">
                        make fetch-rates
                    </code>
                    .
                </p>
            </div>
        </AppLayout>
    );
}
