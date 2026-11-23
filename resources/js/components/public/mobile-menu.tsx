import { Link } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import { motion, useReducedMotion } from 'motion/react';
import type { Variants } from 'motion/react';
import { useState } from 'react';
import BrandWordmark from '@/components/public/brand-wordmark';
import { publicNavItems } from '@/components/public/nav-items';
import type { AuthNavItem } from '@/components/public/nav-items';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';

export default function MobileMenu({
    authItems,
}: {
    authItems: AuthNavItem[];
}) {
    const [open, setOpen] = useState(false);
    const reduceMotion = useReducedMotion();

    const list: Variants = {
        hidden: {},
        show: {
            transition: reduceMotion
                ? {}
                : { staggerChildren: 0.07, delayChildren: 0.15 },
        },
    };
    const entry: Variants = reduceMotion
        ? { hidden: { opacity: 0 }, show: { opacity: 1 } }
        : {
              hidden: { opacity: 0, y: 24 },
              show: {
                  opacity: 1,
                  y: 0,
                  transition: { duration: 0.5, ease: [0.22, 1, 0.36, 1] },
              },
          };

    return (
        <Sheet open={open} onOpenChange={setOpen}>
            <SheetTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label={open ? 'Tutup menu' : 'Buka menu'}
                    className="rounded-full"
                >
                    <Menu className="size-5" />
                </Button>
            </SheetTrigger>
            <SheetContent
                side="right"
                aria-describedby={undefined}
                className="surface-public dark w-full gap-0 border-border bg-background p-0 text-foreground sm:max-w-sm"
            >
                <SheetTitle className="sr-only">Menu</SheetTitle>
                <div className="flex h-16 items-center px-5">
                    <BrandWordmark />
                </div>

                <nav aria-label="Menu seluler" className="flex-1 px-5 pt-10">
                    <motion.ul
                        className="flex flex-col gap-5"
                        variants={list}
                        initial="hidden"
                        animate="show"
                    >
                        {publicNavItems.map((item, index) => (
                            <motion.li key={item.title} variants={entry}>
                                <Link
                                    href={item.href}
                                    onClick={() => setOpen(false)}
                                    className="group flex items-baseline gap-4 rounded-sm focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                                >
                                    <span className="label-spec text-ember">
                                        {String(index + 1).padStart(2, '0')}
                                    </span>
                                    <span className="font-display text-5xl leading-none font-bold uppercase transition-colors group-hover:text-ember">
                                        {item.title}
                                    </span>
                                </Link>
                            </motion.li>
                        ))}
                    </motion.ul>
                </nav>

                <motion.div
                    className="flex flex-col gap-3 border-t border-border p-5"
                    variants={entry}
                    initial="hidden"
                    animate="show"
                >
                    {authItems.map((item) => (
                        <Button
                            key={item.title}
                            asChild
                            size="lg"
                            variant={item.primary ? 'default' : 'outline'}
                            className={cn(
                                'w-full rounded-full font-semibold',
                                !item.primary && 'bg-transparent',
                            )}
                        >
                            <Link
                                href={item.href}
                                onClick={() => setOpen(false)}
                            >
                                {item.title}
                            </Link>
                        </Button>
                    ))}
                </motion.div>
            </SheetContent>
        </Sheet>
    );
}
