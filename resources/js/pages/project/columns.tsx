"use client"

import { ColumnDef } from "@tanstack/react-table"
import { type Project } from "@/types";
import { Button } from "@/components/ui/button"
import { Clock, MoreHorizontal } from "lucide-react"

import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"

export const columns: ColumnDef<Project>[] = [
 
 {
    accessorKey: "name",
    header: () => <div className="max-w-xs text-accent-foreground">Nom</div>,
  },
   {
    accessorKey: "description",
    header: () => <div className="max-w-xs text-accent-foreground">Description</div>,
    cell: ({ row }) => {
      const description = row.getValue("description") as string
      return <div className="max-w-xl">{description}</div>
    },
  },
  {
    accessorKey: "status",
    header: () => <div className="max-w-xs text-accent-foreground">Status</div>,
    cell: ({ row }) => {
      const status = row.getValue("status") as string
      const statusMap: Record<string, string> = {
        pending: "En attente",
        inprogress: "En cours",
        completed: "Terminé"
      }
      return (
        <>
         <Clock/> {status}
        </>
        

      ) 
    },
  },
  {
    accessorKey: "end_date",
    header: () => <div className="max-w-xs text-accent-foreground">Date de fin</div>,
    cell: ({ row }) => {
      const end_date = parseFloat(row.getValue("end_date"))
      
      return <div className="font-medium">{end_date}</div>
    },
  },
  {
    id: "actions",
    cell: ({ row }) => {
      const Project = row.original
 
      return (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" className="h-8 w-8 p-0">
              <span className="sr-only">Open menu</span>
              <MoreHorizontal className="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuLabel>Actions</DropdownMenuLabel>
            <DropdownMenuItem
              onClick={() => navigator.clipboard.writeText(String(Project.id))}
            >
              copier l'ID du projet
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem>Voir tâches</DropdownMenuItem>
            <DropdownMenuItem>Modifier Projet</DropdownMenuItem>

          </DropdownMenuContent>
        </DropdownMenu>
      )
    },
  },
]