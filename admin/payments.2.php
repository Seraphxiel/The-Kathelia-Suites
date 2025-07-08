                    <td><?= htmlspecialchars($p['reference_number']) ?></td>
                    <td>
                        <?php if (!empty($p['proof_url'])): ?>
                            <?php
                                $proofPath = htmlspecialchars($p['proof_url']);
                                // Check if the path already includes the base directory
                                if (strpos($proofPath, '/kathelia-suites/') === 0) {
                                    $displayPath = $proofPath;
                                } else {
                                    $displayPath = '/kathelia-suites/' . $proofPath;
                                }
                            ?>
                            <a href="<?= $displayPath ?>" target="_blank">View Proof</a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td><?= date('M j, Y g:i a', strtotime($p['payment_date'])) ?></td> 