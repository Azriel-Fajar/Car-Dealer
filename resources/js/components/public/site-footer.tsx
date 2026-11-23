import { Link } from '@inertiajs/react';
import BrandWordmark from '@/components/public/brand-wordmark';
import { publicNavItems } from '@/components/public/nav-items';
import { home } from '@/routes';

const contacts = [
    { label: 'Alamat', value: 'Jl. Teuku Umar No. 88, Denpasar, Bali' },
    { label: 'Telepon', value: '(0361) 123 456', href: 'tel:+62361123456' },
    {
        label: 'Email',
        value: 'halo@pemecut.test',
        href: 'mailto:halo@pemecut.test',
    },
];

export default function SiteFooter() {
    const year = new Date().getFullYear();

    return (
        <footer className="relative overflow-hidden border-t border-border">
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-x-0 top-0 h-px bg-linear-to-r from-transparent via-ember/60 to-transparent"
            />
            <div className="mx-auto grid max-w-screen-2xl gap-12 px-5 py-16 md:grid-cols-12 md:px-10 md:py-20">
                <div className="flex flex-col gap-5 md:col-span-5">
                    <Link href={home()} className="w-fit text-foreground">
                        <BrandWordmark className="text-3xl" />
                    </Link>
                    <p className="max-w-sm text-sm leading-relaxed text-muted-foreground">
                        Dealer mobil baru &amp; bekas pilihan — jual beli,
                        kredit, servis &amp; trade-in.
                    </p>
                </div>

                <nav aria-label="Navigasi footer" className="md:col-span-3">
                    <p className="mb-4 label-spec text-muted-foreground">
                        Jelajahi
                    </p>
                    <ul className="flex flex-col gap-2">
                        {publicNavItems.map((item) => (
                            <li key={item.title}>
                                <Link
                                    href={item.href}
                                    className="text-sm text-foreground transition-colors hover:text-ember"
                                >
                                    {item.title}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </nav>

                <address className="not-italic md:col-span-4">
                    <p className="mb-4 label-spec text-muted-foreground">
                        Kontak
                    </p>
                    <ul className="flex flex-col gap-2 text-sm">
                        {contacts.map((contact) => (
                            <li key={contact.label}>
                                <span className="sr-only">
                                    {contact.label}:{' '}
                                </span>
                                {contact.href ? (
                                    <a
                                        href={contact.href}
                                        className="transition-colors hover:text-ember"
                                    >
                                        {contact.value}
                                    </a>
                                ) : (
                                    contact.value
                                )}
                            </li>
                        ))}
                    </ul>
                </address>
            </div>

            <div className="border-t border-border">
                <div className="mx-auto max-w-screen-2xl px-5 py-6 md:px-10">
                    <p className="label-spec text-muted-foreground">
                        © {year} Pemecut Motors. Hak cipta dilindungi.
                    </p>
                </div>
            </div>
        </footer>
    );
}
