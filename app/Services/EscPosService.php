<?php

namespace App\Services;

use App\Models\KitchenTicket;
use App\Models\StationPrinter;

class EscPosService
{
    /**
     * Attempt to dispatch a Kitchen Ticket to a configured TCP/IP ESC/POS thermal printer.
     * Fails gracefully if the printer is unreachable (e.g., cloud server without LAN VPN),
     * while returning the formatted slip text for browser @media print fallback.
     */
    public function dispatchKitchenTicket(KitchenTicket $ticket): array
    {
        $ticket->loadMissing(['table', 'server', 'items']);

        $slip = $this->formatSlip($ticket);
        $printer = StationPrinter::where('tenant_id', $ticket->tenant_id)
            ->where('station', $ticket->station)
            ->where('is_active', true)
            ->first();

        if (!$printer || empty($printer->printer_ip)) {
            return ['sent' => false, 'reason' => 'No active network printer IP configured', 'slip' => $slip];
        }

        try {
            $fp = @fsockopen($printer->printer_ip, $printer->printer_port ?: 9100, $errno, $errstr, 2);
            if (!$fp) {
                return ['sent' => false, 'reason' => $errstr ?: 'Connection failed', 'slip' => $slip];
            }

            // ESC/POS: Initialize + Bold/Double-height header + Slip + Cut
            $escInit = "\x1B\x40";
            $escCut = "\n\n\n\x1D\x56\x00";
            fwrite($fp, $escInit . $slip . $escCut);
            fclose($fp);

            $ticket->update(['printed_at' => now()]);

            return ['sent' => true, 'printer' => $printer->printer_name, 'slip' => $slip];
        } catch (\Throwable $e) {
            return ['sent' => false, 'reason' => $e->getMessage(), 'slip' => $slip];
        }
    }

    public function formatSlip(KitchenTicket $ticket): string
    {
        $tableLabel = $ticket->table ? 'TABLE ' . $ticket->table->table_number : 'TAKEAWAY';
        $serverName = $ticket->server?->name ?? 'Staff';
        $station = strtoupper($ticket->station);

        $lines = [
            "================================",
            "   KITCHEN ORDER TICKET ({$station})",
            "================================",
            "Ticket #: {$ticket->id}",
            "Table:    {$tableLabel}",
            "Server:   {$serverName}",
            "Time:     " . ($ticket->created_at?->format('H:i:s d/m/Y') ?? now()->format('H:i:s')),
            "--------------------------------",
        ];

        foreach ($ticket->items as $item) {
            $lines[] = "{$item->quantity}x {$item->item_name}";
            if ($item->modifiers_summary) {
                $lines[] = "   + {$item->modifiers_summary}";
            }
            if ($item->special_notes) {
                $lines[] = "   ** NOTE: " . strtoupper($item->special_notes) . " **";
            }
        }

        $lines[] = "================================";

        return implode("\n", $lines);
    }
}

