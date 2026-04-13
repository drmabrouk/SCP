<?php
global $wpdb;
$current_user = Control_Auth::current_user();
if ( Control_Auth::is_admin() ) {
    $total_users = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}control_staff");
    $stats = Control_Investments::get_system_wide_stats('live');
    ?>
    <div class="control-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:#1e293b;"><?php _e('لوحة تحكم النظام', 'control'); ?></h2>
    </div>

    <!-- Administrative Overview Metrics (Key KPIs at top for mobile) -->
    <div class="control-metrics-grid" style="margin-bottom:25px;">
        <div class="control-metric-card">
            <div class="control-metric-icon" style="background: #f8fafc; color: #D4AF37;">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="control-metric-content">
                <div class="control-metric-title"><?php _e('رأس المال المستثمر', 'control'); ?></div>
                <div class="control-metric-value"><?php echo number_format($stats['total_invested']); ?></div>
                <small style="color:#059669; font-size:0.6rem;"><?php _e('تمويل فعلي', 'control'); ?></small>
            </div>
        </div>
        <div class="control-metric-card">
            <div class="control-metric-icon" style="background: #f8fafc; color: #059669;">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="control-metric-content">
                <div class="control-metric-title"><?php _e('الإشغال الكلي', 'control'); ?></div>
                <div class="control-metric-value"><?php echo $stats['occupancy_rate']; ?>%</div>
            </div>
        </div>
        <div class="control-metric-card">
            <div class="control-metric-icon" style="background: #f8fafc; color: #000;">
                <span class="dashicons dashicons-chart-area"></span>
            </div>
            <div class="control-metric-content">
                <div class="control-metric-title"><?php _e('النمو الشهري', 'control'); ?></div>
                <div class="control-metric-value" style="color:<?php echo $stats['growth_rate'] >= 0 ? '#059669' : '#ef4444'; ?>;">
                    <?php echo ($stats['growth_rate'] >= 0 ? '+' : '') . $stats['growth_rate']; ?>%
                </div>
            </div>
        </div>
    </div>


    <div class="control-grid main-dashboard-grid" style="grid-template-columns: 2fr 1fr; gap: 25px;">
        <div class="control-card" style="border-top: 5px solid #000;">
            <h3 style="margin-bottom:20px;"><?php _e('الأداء التشغيلي والمالي', 'control'); ?></h3>
            <div class="control-grid" style="grid-template-columns: repeat(2, 1fr); gap: 15px;">
                <div style="background:#f8fafc; padding:12px; border-radius:12px; border:1px solid #e2e8f0;">
                    <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e('المستثمرون', 'control'); ?></small>
                    <div style="font-size:1.1rem; font-weight:800;"><?php echo number_format($stats['investor_count']); ?></div>
                </div>
                <div style="background:#f8fafc; padding:12px; border-radius:12px; border:1px solid #e2e8f0;">
                    <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e('المستخدمون', 'control'); ?></small>
                    <div style="font-size:1.1rem; font-weight:800;"><?php echo number_format($total_users); ?></div>
                </div>
            </div>
            <div class="control-grid" style="grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top:15px;">
                <div style="background:#f8fafc; padding:20px; border-radius:12px; border:1px solid #e2e8f0;">
                    <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e('إجمالي الإيرادات', 'control'); ?></small>
                    <div style="font-size:1.2rem; font-weight:800; color:#059669;"><?php echo number_format($stats['total_revenue']); ?></div>
                    <small style="font-size:0.65rem; color:#94a3b8;"><?php _e('التدفقات النقدية الداخلة', 'control'); ?></small>
                </div>
                <div style="background:#f8fafc; padding:20px; border-radius:12px; border:1px solid #e2e8f0;">
                    <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e('المصروفات التشغيلية', 'control'); ?></small>
                    <div style="font-size:1.2rem; font-weight:800; color:#ef4444;"><?php echo number_format($stats['total_expenses']); ?></div>
                    <small style="font-size:0.65rem; color:#94a3b8;"><?php _e('التدفقات النقدية الخارجة', 'control'); ?></small>
                </div>
                <div style="background:#000; padding:20px; border-radius:12px; border:1px solid #000;">
                    <small style="display:block; color:#94a3b8; margin-bottom:5px;"><?php _e('صافي الربح', 'control'); ?></small>
                    <div style="font-size:1.2rem; font-weight:800; color:#D4AF37;"><?php echo number_format($stats['net_profit']); ?></div>
                    <small style="font-size:0.65rem; color:#94a3b8;"><?php _e('صافي حركة النقد', 'control'); ?></small>
                </div>
            </div>

        </div>

        <div class="control-card">
            <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin:0;"><?php _e('آخر 5 عمليات', 'control'); ?></h3>
                <a href="<?php echo add_query_arg('control_view', 'settings'); ?>#tab-audit" style="font-size:0.7rem; color:#D4AF37; font-weight:700; text-decoration:none;"><?php _e('عرض الكل', 'control'); ?></a>
            </div>
            <div style="max-height: 400px; overflow-y: auto;">
                <?php
                $recent_logs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}control_activity_logs ORDER BY action_date DESC LIMIT 5");
                foreach($recent_logs as $log): ?>
                    <div style="padding: 10px 0; border-bottom: 1px solid #f1f5f9;">
                        <small style="display:block; color:#64748b;"><?php echo date('H:i - Y/m/d', strtotime($log->action_date)); ?></small>
                        <div style="font-size:0.8rem; font-weight:600;"><?php echo esc_html($log->description); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
} elseif ( Control_Auth::is_investor() ) {
    $perf = Control_Investments::get_investor_performance($current_user->id);
    $wallet_obj = Control_Investments::get_wallet($current_user->id);
    $wallet = $wallet_obj->balance;
    $my_properties = Control_Investments::get_investor_properties($current_user->id);

    // Switch between properties
    $active_prop_id = isset($_GET['prop_id']) ? intval($_GET['prop_id']) : (isset($my_properties[0]) ? $my_properties[0]->id : 0);

    $active_property = null;
    $prop_perf = null;
    $rooms = array();
    $contribution = 0;

    if ($active_prop_id) {
        $active_property = Control_Properties::get_property($active_prop_id);
        $prop_perf = Control_Properties::get_property_performance($active_prop_id);
        $rooms = Control_Properties::get_rooms($active_prop_id);

        foreach($my_properties as $p) {
            if ($p->id == $active_prop_id) {
                $contribution = $p->my_contribution;
                break;
            }
        }
    }

    $total_profit = $wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM {$wpdb->prefix}control_transactions WHERE user_id = %d AND type = 'dividend'", $current_user->id)) ?: 0;
    $overall_roi = ($perf['total_invested'] > 0) ? ($total_profit / $perf['total_invested']) * 100 : 0;
    ?>

    <div class="control-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:#1e293b;"><?php _e('لوحة تحكم المستثمر', 'control'); ?></h2>

        <div style="display:flex; gap:10px;">
            <?php if (count($my_properties) > 1): ?>
                <div style="background:#fff; padding:5px 15px; border-radius:8px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:10px;">
                    <span style="font-size:0.8rem; font-weight:700; color:#64748b;"><?php _e('تغيير العقار:', 'control'); ?></span>
                    <select onchange="window.location.href='<?php echo add_query_arg('prop_id', '', add_query_arg('control_view', 'dashboard')); ?>' + this.value" style="border:none; font-weight:700; cursor:pointer;">
                        <?php foreach($my_properties as $p): ?>
                            <option value="<?php echo $p->id; ?>" <?php selected($active_prop_id, $p->id); ?>><?php echo esc_html($p->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Wallet Section -->
    <div class="control-card" style="border-top: 5px solid #D4AF37; margin-bottom: 25px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="margin:0;"><?php _e('المحفظة الاستثمارية والسيولة', 'control'); ?></h3>
            <span class="control-capsule" style="background:#000; color:#D4AF37;"><?php _e('حساب حقيقي (AED)', 'control'); ?></span>
        </div>

        <div class="control-grid" style="grid-template-columns: 1.5fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div style="background:#000; color:#fff; padding:25px; border-radius:15px; position:relative; overflow:hidden;">
                <div style="position:relative; z-index:2;">
                    <small style="opacity:0.7; display:block; margin-bottom:5px;"><?php _e('صافي الرصيد الحالي', 'control'); ?></small>
                    <div style="font-size:2.2rem; font-weight:800; color:#D4AF37; line-height:1;"><?php echo number_format($wallet_obj->balance, 2); ?></div>
                    <div style="margin-top:10px; font-size:0.75rem; opacity:0.6;"><?php _e('بعد خصم كافة الالتزامات والمصاريف', 'control'); ?></div>
                </div>
                <span class="dashicons dashicons-shield" style="position:absolute; right:-10px; bottom:-10px; font-size:100px; width:100px; height:100px; opacity:0.05;"></span>
            </div>
            <div style="background:#f0fdf4; border:1px solid #dcfce7; padding:20px; border-radius:12px; display:flex; flex-direction:column; justify-content:center;">
                <small style="color:#166534; font-weight:700; margin-bottom:8px; display:flex; align-items:center; gap:5px;">
                    <span class="dashicons dashicons-yes-alt" style="font-size:16px; width:16px; height:16px;"></span> <?php _e('متاح للسحب الآن', 'control'); ?>
                </small>
                <div style="font-size:1.6rem; font-weight:800; color:#15803d;"><?php echo number_format($wallet_obj->available_balance, 2); ?></div>
                <small style="color:#166534; opacity:0.6; font-size:0.65rem; margin-top:5px;"><?php _e('* محول من دورات سابقة', 'control'); ?></small>
            </div>
            <div style="background:#fff7ed; border:1px solid #ffedd5; padding:20px; border-radius:12px; display:flex; flex-direction:column; justify-content:center;">
                <small style="color:#9a3412; font-weight:700; margin-bottom:8px; display:flex; align-items:center; gap:5px;">
                    <span class="dashicons dashicons-clock" style="font-size:16px; width:16px; height:16px;"></span> <?php _e('أرباح الشهر (معلقة)', 'control'); ?>
                </small>
                <div style="font-size:1.6rem; font-weight:800; color:#c2410c;"><?php echo number_format($wallet_obj->pending_balance, 2); ?></div>
                <small style="color:#9a3412; opacity:0.6; font-size:0.65rem; margin-top:5px;"><?php _e('* تصدر في نهاية الشهر', 'control'); ?></small>
            </div>
        </div>

        <div class="control-grid" style="grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom:20px;">
            <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:15px; border-radius:10px;">
                <small style="color:#64748b; display:block; margin-bottom:5px;"><?php _e('إجمالي التحصيلات (عوائد)', 'control'); ?></small>
                <?php $total_in = $wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM {$wpdb->prefix}control_transactions WHERE user_id = %d AND amount > 0", $current_user->id)) ?: 0; ?>
                <div style="font-weight:800; color:#059669;"><?php echo number_format($total_in, 2); ?></div>
            </div>
            <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:15px; border-radius:10px;">
                <small style="color:#64748b; display:block; margin-bottom:5px;"><?php _e('إجمالي الاستقطاعات', 'control'); ?></small>
                <?php $total_out = $wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM {$wpdb->prefix}control_transactions WHERE user_id = %d AND amount < 0", $current_user->id)) ?: 0; ?>
                <div style="font-weight:800; color:#ef4444;"><?php echo number_format(abs($total_out), 2); ?></div>
            </div>
            <div style="background:#fefce8; border:1px solid #fef08a; padding:15px; border-radius:10px;">
                <small style="color:#854d0e; display:block; margin-bottom:5px;"><?php _e('المبالغ المحجوزة', 'control'); ?></small>
                <div style="font-weight:800; color:#854d0e;"><?php echo number_format($wallet_obj->reserved_balance, 2); ?></div>
            </div>
        </div>
        <div class="control-metrics-grid">
            <div class="control-metric-card" style="background:#f8fafc;">
                <div class="control-metric-content">
                    <div class="control-metric-title"><?php _e('رأس المال المستثمر', 'control'); ?></div>
                    <div class="control-metric-value"><?php echo number_format($perf['total_invested']); ?></div>
                </div>
            </div>
            <div class="control-metric-card" style="background:#f8fafc;">
                <div class="control-metric-content">
                    <div class="control-metric-title"><?php _e('إجمالي الأرباح المستلمة', 'control'); ?></div>
                    <div class="control-metric-value"><?php echo number_format($total_profit); ?></div>
                </div>
            </div>
            <div class="control-metric-card" style="background:#f8fafc;">
                <div class="control-metric-content">
                    <div class="control-metric-title"><?php _e('عائد ROI تراكمي', 'control'); ?></div>
                    <div class="control-metric-value"><?php echo round($overall_roi, 2); ?>%</div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($active_property):
        $rented_rooms = array_filter($rooms, function($r){ return $r->status === 'rented'; });
        $vacant_rooms = array_filter($rooms, function($r){ return $r->status === 'available'; });
        $total_project_cost = floatval($active_property->base_value) + floatval($active_property->total_setup_cost);
        $share_percent = ($total_project_cost > 0) ? ($contribution / $total_project_cost) * 100 : 0;
    ?>
        <div class="control-grid investor-main-grid" style="grid-template-columns: 2fr 1fr; gap: 25px;">
            <div class="control-column">
                <!-- Property Analytics -->
                <div class="control-card" style="border-top: 5px solid #D4AF37;">
                    <h3 style="display:flex; justify-content: space-between; align-items: center;">
                        <span><?php echo esc_html($active_property->name); ?> - <?php _e('تحليلات الأداء', 'control'); ?></span>
                        <span class="control-capsule" style="background:#000; font-size:0.75rem;"><?php echo ($active_property->property_type === 'leased') ? __('إدارة وتشغيل', 'control') : __('ملكية عقارية', 'control'); ?></span>
                    </h3>

                    <div class="control-grid control-analytics-grid" style="grid-template-columns: repeat(4, 1fr); gap:15px; margin-top:20px;">
                        <div style="background:#f8fafc; padding:12px; border-radius:10px; border:1px solid #e2e8f0; text-align:center;">
                            <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e('مساهمتي', 'control'); ?></small>
                            <span style="font-weight:800; font-size:0.95rem; color:#000;"><?php echo number_format($contribution); ?></span>
                        </div>
                        <div style="background:#f8fafc; padding:12px; border-radius:10px; border:1px solid #e2e8f0; text-align:center;">
                            <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e('نسبة الملكية', 'control'); ?></small>
                            <span style="font-weight:800; font-size:0.95rem; color:#D4AF37;"><?php echo round($share_percent, 2); ?>%</span>
                        </div>
                        <div style="background:#f8fafc; padding:12px; border-radius:10px; border:1px solid #e2e8f0; text-align:center;">
                            <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e(' ROI المشروع', 'control'); ?></small>
                            <span style="font-weight:800; font-size:0.95rem; color:#059669;"><?php echo $prop_perf['roi']; ?>%</span>
                        </div>
                        <div style="background:#f8fafc; padding:12px; border-radius:10px; border:1px solid #e2e8f0; text-align:center;">
                            <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e('إشغال العقار', 'control'); ?></small>
                            <span style="font-weight:800; font-size:0.95rem; color:#000;"><?php echo round((count($rented_rooms) / (count($rooms) ?: 1)) * 100); ?>%</span>
                        </div>
                    </div>

                    <div style="margin-top:30px; padding:20px; background:#000; color:#fff; border-radius:12px; display:flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h4 style="margin:0; font-size:0.9rem; opacity:0.8;"><?php _e('أرباحي الشهرية (الواقع الفعلي)', 'control'); ?></h4>
                            <div style="font-size:1.8rem; font-weight:800; margin-top:5px; color:#D4AF37;"><?php echo number_format($prop_perf['monthly_net'] * ($share_percent / 100), 2); ?> AED</div>
                        </div>
                        <span class="dashicons dashicons-chart-bar" style="font-size:40px; width:40px; height:40px; opacity:0.3;"></span>
                    </div>

                    <!-- Projected Performance Section -->
                    <div style="margin-top:20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:20px;">
                        <h4 style="margin:0 0 15px 0; font-size:0.9rem; color:#000; font-weight:800;"><?php _e('تحليل العائد الاستثماري المتوقع', 'control'); ?></h4>
                        <div class="control-grid" style="grid-template-columns: repeat(3, 1fr); gap:15px;">
                            <div>
                                <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e('إجمالي استثماري', 'control'); ?></small>
                                <span style="font-weight:700; color:#000;"><?php echo number_format($contribution); ?></span>
                            </div>
                            <div>
                                <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e('الدخل الشهري المتوقع', 'control'); ?></small>
                                <span style="font-weight:700; color:#059669;"><?php echo number_format($prop_perf['projected_monthly_revenue'] / ($investor_count ?: 3)); ?></span>
                            </div>
                            <div>
                                <small style="display:block; color:#64748b; margin-bottom:5px;"><?php _e('العائد السنوي ROI %', 'control'); ?></small>
                                <span style="font-weight:800; color:#D4AF37;"><?php echo $prop_perf['projected_annual_roi']; ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Transparency -->
                <div class="control-card">
                    <h3 style="margin-bottom:20px;"><?php _e('الشفافية المالية والتدفقات', 'control'); ?></h3>
                    <div style="display:flex; gap:20px;">
                        <div style="flex:1; border:1px solid #e2e8f0; border-radius:10px; padding:20px;">
                            <h4 style="margin:0 0 15px 0; font-size:0.85rem; color:#059669; display:flex; align-items:center; gap:8px;">
                                <span class="dashicons dashicons-arrow-down-alt" style="font-size:16px;"></span> <?php _e('الإيرادات (إيجارات الوحدات)', 'control'); ?>
                            </h4>
                            <div style="font-size:1.4rem; font-weight:800;"><?php echo number_format($prop_perf['income'], 2); ?> <small style="font-size:0.7rem; color:#64748b;">AED</small></div>
                        </div>
                        <div style="flex:1; border:1px solid #e2e8f0; border-radius:10px; padding:20px;">
                            <h4 style="margin:0 0 15px 0; font-size:0.85rem; color:#ef4444; display:flex; align-items:center; gap:8px;">
                                <span class="dashicons dashicons-arrow-up-alt" style="font-size:16px;"></span> <?php _e('تكاليف التجهيز والتشغيل', 'control'); ?>
                            </h4>
                            <div style="font-size:1.4rem; font-weight:800;"><?php echo number_format($prop_perf['expenses'], 2); ?> <small style="font-size:0.7rem; color:#64748b;">AED</small></div>
                                <div style="margin-top:10px; padding-top:10px; border-top:1px dashed #eee;">
                                    <small style="display:block; color:#64748b;"><?php _e('حصتي من الثابت الشهري:', 'control'); ?></small>
                                    <span style="font-weight:700; color:#ef4444;"><?php echo number_format($prop_perf['monthly_fixed_cost_per_investor']); ?></span>
                                </div>
                        </div>
                    </div>
                </div>

                <!-- Transaction History -->
                <div class="control-card">
                    <h3 style="margin-bottom:20px;"><?php _e('سجل الحركات المالية (العوائد والخصومات)', 'control'); ?></h3>
                    <div class="control-table-container">
                    <table class="control-table" style="font-size:0.85rem;">
                        <thead>
                            <tr>
                                <th><?php _e('التاريخ', 'control'); ?></th>
                                <th><?php _e('الوصف', 'control'); ?></th>
                                <th><?php _e('النوع', 'control'); ?></th>
                                <th><?php _e('المبلغ', 'control'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $txs = Control_Investments::get_transactions($current_user->id);
                            if (empty($txs)): ?>
                                <tr><td colspan="4" style="text-align:center; padding:20px;"><?php _e('لا توجد حركات مالية مسجلة', 'control'); ?></td></tr>
                            <?php else: ?>
                                <?php foreach($txs as $tx): ?>
                                    <tr>
                                        <td><?php echo date('Y/m/d', strtotime($tx->transaction_date)); ?></td>
                                        <td><?php echo esc_html($tx->description); ?></td>
                                        <td>
                                            <?php
                                            $types = array('dividend' => 'ربح/عائد', 'investment' => 'مساهمة', 'payout' => 'سحب رصيد');
                                            echo $types[$tx->type] ?? $tx->type;
                                            ?>
                                        </td>
                                        <td style="font-weight:700; color:<?php echo ($tx->type === 'dividend') ? '#059669' : (($tx->type === 'investment' || $tx->type === 'payout') ? '#ef4444' : '#000'); ?>;">
                                            <?php echo ($tx->type === 'dividend') ? '+' : '-'; ?> <?php echo number_format($tx->amount); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

            <div class="control-column">
                <!-- Room Status Visualization -->
                <div class="control-card" style="border-right: 4px solid #000;">
                    <h3 style="margin-bottom:20px;"><?php _e('حالة الوحدات الإيجارية', 'control'); ?></h3>
                    <div style="margin-bottom:25px; display:flex; align-items: center; justify-content: space-between;">
                        <div style="text-align:center;">
                            <div style="font-size:1.5rem; font-weight:800;"><?php echo count($rooms); ?></div>
                            <small style="color:#64748b;"><?php _e('الإجمالي', 'control'); ?></small>
                        </div>
                        <div style="text-align:center;">
                            <div style="font-size:1.5rem; font-weight:800; color:#ef4444;"><?php echo count($rented_rooms); ?></div>
                            <small style="color:#64748b;"><?php _e('مؤجرة', 'control'); ?></small>
                        </div>
                        <div style="text-align:center;">
                            <div style="font-size:1.5rem; font-weight:800; color:#059669;"><?php echo count($vacant_rooms); ?></div>
                            <small style="color:#64748b;"><?php _e('شاغرة', 'control'); ?></small>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: repeat(5, 1fr); gap:8px;">
                        <?php foreach($rooms as $r): ?>
                            <div title="<?php echo ($r->status === 'rented') ? __('مؤجرة', 'control') : __('شاغرة', 'control'); ?>"
                                 style="aspect-ratio:1/1; border-radius:6px; background:<?php echo ($r->status === 'rented') ? '#ef4444' : '#059669'; ?>; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#fff; padding:5px; border: 1px solid rgba(0,0,0,0.1);">
                                <span style="font-weight:800; font-size:0.8rem; line-height:1;"><?php echo $r->room_number; ?></span>
                                <span style="font-size:0.5rem; opacity:0.9; margin-top:2px; font-weight:600;"><?php echo number_format($r->rental_price); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top:25px; padding-top:20px; border-top:1px solid #eee;">
                        <div style="margin-bottom:15px;">
                            <h4 style="font-size:0.8rem; margin:0 0 8px 0; color:#ef4444;"><?php _e('قائمة الوحدات المؤجرة:', 'control'); ?></h4>
                            <div style="display:flex; flex-wrap:wrap; gap:5px;">
                                <?php foreach($rented_rooms as $rr): ?>
                                    <span style="background:#fef2f2; border:1px solid #fee2e2; color:#ef4444; padding:2px 8px; border-radius:4px; font-size:0.75rem; font-weight:700;">#<?php echo $rr->room_number; ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div>
                            <h4 style="font-size:0.8rem; margin:0 0 8px 0; color:#059669;"><?php _e('قائمة الوحدات الشاغرة:', 'control'); ?></h4>
                            <div style="display:flex; flex-wrap:wrap; gap:5px;">
                                <?php foreach($vacant_rooms as $vr): ?>
                                    <span style="background:#f0fdf4; border:1px solid #dcfce7; color:#059669; padding:2px 8px; border-radius:4px; font-size:0.75rem; font-weight:700;">#<?php echo $vr->room_number; ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    <?php endif; ?>
<?php
} elseif ( Control_Auth::is_tenant() ) {
    ?>
    <div class="control-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:#1e293b;"><?php _e('لوحة المستأجر', 'control'); ?></h2>
    </div>
    <div class="control-card" style="border-radius: 12px;">
        <p><?php _e('أهلاً بك. يمكنك متابعة دفعات الإيجار الخاصة بك من هنا قريباً.', 'control'); ?></p>
    </div>
    <?php
} else {
    ?>
    <div class="control-header-flex" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="font-weight:800; font-size:1.5rem; margin:0; color:#1e293b;"><?php _e('لوحة المعلومات', 'control'); ?></h2>
    </div>
    <div class="control-card" style="border-radius: 12px;">
        <p><?php _e('برجاء التواصل مع الإدارة لتحديد دورك في النظام.', 'control'); ?></p>
    </div>
    <?php
}
?>
