import * as React from "react"
import { Slot } from "@radix-ui/react-slot"
import { cva } from "class-variance-authority"
import { cn } from "@/lib/utils"

const buttonVariants = cva(
  "inline-flex items-center justify-center whitespace-nowrap rounded-2xl text-xs font-bold ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 active:scale-[0.98]",
  {
    variants: {
      variant: {
        default: "bg-[#0FA172] text-white hover:bg-[#1CB67C] shadow-md shadow-emerald-600/20",
        destructive:
          "bg-rose-600 text-white hover:bg-rose-700 shadow-md shadow-rose-600/20",
        outline:
          "border border-slate-200 bg-white hover:bg-slate-50 hover:text-slate-900 text-slate-700 shadow-xs",
        secondary:
          "bg-slate-100 text-slate-900 hover:bg-slate-200",
        ghost: "hover:bg-slate-100 hover:text-slate-900 text-slate-600",
        link: "text-emerald-600 underline-offset-4 hover:underline",
        emerald: "bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100",
        amber: "bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100",
        blue: "bg-blue-50 text-blue-800 border border-blue-200 hover:bg-blue-100",
      },
      size: {
        default: "h-10 px-4 py-2",
        sm: "h-8 rounded-xl px-3 text-[11px]",
        lg: "h-12 rounded-2xl px-6 text-sm font-extrabold",
        icon: "h-9 w-9 rounded-xl",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
)

const Button = React.forwardRef(({ className, variant, size, asChild = false, ...props }, ref) => {
  const Comp = asChild ? Slot : "button"
  return (
    <Comp
      className={cn(buttonVariants({ variant, size, className }))}
      ref={ref}
      {...props}
    />
  )
})
Button.displayName = "Button"

export { Button, buttonVariants }
